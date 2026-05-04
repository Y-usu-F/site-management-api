<?php

namespace App\Services\Operation;

use App\Core\BaseService;
use App\Exceptions\ConflictApiException;
use App\Exceptions\NotFoundApiException;
use App\Exceptions\TenantAccessDeniedException;
use App\Libraries\ListQuery;
use App\Models\CommonAreaReservationModel;
use Config\Database;
use DateTimeImmutable;
use DateTimeZone;
use Throwable;

class CommonAreaReservationService extends BaseService
{
    public function __construct(private readonly CommonAreaReservationModel $model = new CommonAreaReservationModel())
    {
        parent::__construct();
    }

    public function list(array $query): array
    {
        $q = ListQuery::normalize($query, [
            'sortable' => ['id', 'reservation_no', 'start_at', 'end_at', 'status', 'created_at'],
            'filterable' => ['common_area_id', 'unit_id', 'resident_profile_id', 'status'],
        ]);
        $b = $this->model->builder()->select('*')->where('deleted_at', null);
        foreach ($q['filters'] as $f => $v) {
            $b->where($f, $v);
        }
        if ($q['search'] !== '') {
            $b->like('reservation_no', $q['search']);
        }
        $t = (int) $b->countAllResults(false);
        $i = $b->orderBy($q['sort'], $q['direction'])->limit($q['per_page'], ($q['page'] - 1) * $q['per_page'])->get()->getResultArray();
        return ListQuery::envelope($q['page'], $q['per_page'], $t, $i);
    }

    public function show(int $id): array
    {
        $this->assertAccessible($id);
        $r = $this->model->tenantFind($id);
        if (!is_array($r)) {
            throw new NotFoundApiException('Reservation bulunamadi');
        }
        return $r;
    }

    public function create(array $payload): array
    {
        $area = $this->assertCommonAreaAccessible((int) $payload['common_area_id']);
        $unitId = $this->toNullableInt($payload, 'unit_id');
        $residentId = $this->toNullableInt($payload, 'resident_profile_id');
        $startDt = $this->parseDateTime((string) $payload['start_at']);
        $endDt = $this->parseDateTime((string) $payload['end_at']);
        $this->assertTimeline($startDt, $endDt);
        $start = $startDt->format('Y-m-d H:i:s');
        $end = $endDt->format('Y-m-d H:i:s');
        $this->assertUnitResidentConsistency($area, $unitId, $residentId);
        $participantCount = $this->toNullableInt($payload, 'participant_count');
        if ($participantCount !== null && isset($area['capacity']) && $area['capacity'] !== null && $participantCount > (int) $area['capacity']) {
            throw new ConflictApiException('participant_count capacity degerini asamaz');
        }
        $status = ((int) ($area['requires_approval'] ?? 1) === 1) ? 'pending' : 'approved';
        $approvedAt = $status === 'approved' ? date('Y-m-d H:i:s') : null;
        $approvedBy = $status === 'approved' ? (service('request')->user?->id ?? null) : null;
        $base = [
            'common_area_id' => (int) $area['id'],
            'unit_id' => $unitId,
            'resident_profile_id' => $residentId,
            'start_at' => $start,
            'end_at' => $end,
            'participant_count' => $participantCount,
            'status' => $status,
            'approved_by' => $approvedBy,
            'approved_at' => $approvedAt,
            'rejected_reason' => null,
            'cancelled_reason' => null,
            'notes' => isset($payload['notes']) ? trim((string) $payload['notes']) : null,
        ];

        $id = $this->runOverlapTransaction((int) $area['id'], $start, $end, null, function () use ($base): int {
            $this->assertNoOverlap((int) $base['common_area_id'], (string) $base['start_at'], (string) $base['end_at'], null, true);
            $this->insertWithNoRetry($base);
            return (int) $this->model->getInsertID();
        });

        $c = $this->show($id);
        $this->audit('operation.common_area_reservation.create.success', ['entity_type' => 'common_area_reservation', 'entity_id' => $id, 'new_values' => $c]);
        return $c;
    }

    public function update(int $id, array $payload): array
    {
        $old = $this->show($id);
        if (in_array((string) $old['status'], ['rejected', 'cancelled', 'completed'], true)) { throw new ConflictApiException('Bu statusde reservation guncellenemez'); }
        $area = $this->assertCommonAreaAccessible((int) $old['common_area_id']);
        $startDt = $this->parseDateTime((string) ($payload['start_at'] ?? $old['start_at']));
        $endDt = $this->parseDateTime((string) ($payload['end_at'] ?? $old['end_at']));
        $this->assertTimeline($startDt, $endDt);
        $start = $startDt->format('Y-m-d H:i:s');
        $end = $endDt->format('Y-m-d H:i:s');

        $unitId = array_key_exists('unit_id', $payload)
            ? $this->toNullableInt($payload, 'unit_id')
            : (isset($old['unit_id']) ? (int) $old['unit_id'] : null);
        $residentId = array_key_exists('resident_profile_id', $payload)
            ? $this->toNullableInt($payload, 'resident_profile_id')
            : (isset($old['resident_profile_id']) ? (int) $old['resident_profile_id'] : null);
        $this->assertUnitResidentConsistency($area, $unitId, $residentId);

        $participantCount = array_key_exists('participant_count', $payload)
            ? $this->toNullableInt($payload, 'participant_count')
            : (isset($old['participant_count']) ? (int) $old['participant_count'] : null);
        if ($participantCount !== null && isset($area['capacity']) && $area['capacity'] !== null && $participantCount > (int) $area['capacity']) {
            throw new ConflictApiException('participant_count capacity degerini asamaz');
        }

        $commonAreaId = (int) $area['id'];
        $this->runOverlapTransaction($commonAreaId, $start, $end, $id, function () use ($payload, $start, $end, $id, $commonAreaId): int {
            $this->assertNoOverlap($commonAreaId, $start, $end, $id, true);
            $d = [];
            foreach (['unit_id', 'resident_profile_id', 'participant_count', 'notes'] as $f) {
                if (array_key_exists($f, $payload)) {
                    $d[$f] = $payload[$f];
                }
            }
            if (array_key_exists('start_at', $payload)) {
                $d['start_at'] = $start;
            }
            if (array_key_exists('end_at', $payload)) {
                $d['end_at'] = $end;
            }
            if ($d !== []) {
                $this->model->update($id, $d);
            }
            return $id;
        });

        $n = $this->show($id);
        $this->audit('operation.common_area_reservation.update.success', ['entity_type' => 'common_area_reservation', 'entity_id' => $id, 'old_values' => $old, 'new_values' => $n]);
        return $n;
    }

    public function approve(int $id): array { $old=$this->show($id); if((string)$old['status']!=='pending'){throw new ConflictApiException('approve sadece pending icin calisir');} $this->model->update($id,['status'=>'approved','approved_by'=>service('request')->user?->id ?? null,'approved_at'=>date('Y-m-d H:i:s'),'rejected_reason'=>null]); $n=$this->show($id); $this->audit('operation.common_area_reservation.approve.success',['entity_type'=>'common_area_reservation','entity_id'=>$id,'old_values'=>$old,'new_values'=>$n]); return $n; }
    public function reject(int $id, ?string $reason): array { $old=$this->show($id); if((string)$old['status']!=='pending'){throw new ConflictApiException('reject sadece pending icin calisir');} $this->model->update($id,['status'=>'rejected','rejected_reason'=>$reason!==null?trim($reason):null]); $n=$this->show($id); $this->audit('operation.common_area_reservation.reject.success',['entity_type'=>'common_area_reservation','entity_id'=>$id,'old_values'=>$old,'new_values'=>$n]); return $n; }
    public function cancel(int $id, ?string $reason): array { $old=$this->show($id); if(!in_array((string)$old['status'],['pending','approved'],true)){throw new ConflictApiException('cancel pending/approved icin calisir');} $this->model->update($id,['status'=>'cancelled','cancelled_reason'=>$reason!==null?trim($reason):null]); $n=$this->show($id); $this->audit('operation.common_area_reservation.cancel.success',['entity_type'=>'common_area_reservation','entity_id'=>$id,'old_values'=>$old,'new_values'=>$n]); return $n; }
    public function complete(int $id): array { $old=$this->show($id); if((string)$old['status']!=='approved'){throw new ConflictApiException('complete sadece approved icin calisir');} $this->model->update($id,['status'=>'completed']); $n=$this->show($id); $this->audit('operation.common_area_reservation.complete.success',['entity_type'=>'common_area_reservation','entity_id'=>$id,'old_values'=>$old,'new_values'=>$n]); return $n; }

    private function assertNoOverlap(int $commonAreaId, string $startAt, string $endAt, ?int $excludeId = null, bool $forUpdate = false): void
    {
        $db = Database::connect();
        $companyId = (int) (service('request')->company_id ?? 0);
        $sql = "SELECT id FROM common_area_reservations
                WHERE company_id = ?
                  AND common_area_id = ?
                  AND deleted_at IS NULL
                  AND status IN ('pending','approved')
                  AND start_at < ?
                  AND end_at > ?";
        $params = [$companyId, $commonAreaId, $endAt, $startAt];
        if ($excludeId !== null) {
            $sql .= " AND id <> ?";
            $params[] = $excludeId;
        }
        $sql .= " LIMIT 1";
        if ($forUpdate) {
            $sql .= " FOR UPDATE";
        }

        $row = $db->query($sql, $params)->getRowArray();
        if ($row !== null) {
            throw new ConflictApiException('Ayni zaman araliginda rezervasyon cakismasi var');
        }
    }
    private function assertTimeline(DateTimeImmutable $startAt, DateTimeImmutable $endAt): void
    {
        if ($startAt >= $endAt) {
            throw new ConflictApiException('start_at end_atten kucuk olmali');
        }
        $now = new DateTimeImmutable('now', new DateTimeZone(date_default_timezone_get()));
        if ($startAt < $now) {
            throw new ConflictApiException('Gecmis tarihe rezervasyon olusturulamaz');
        }
    }
    /** @return array<string,mixed> */ private function assertCommonAreaAccessible(int $id): array { $r=Database::connect()->table('common_areas')->where('id',$id)->where('deleted_at',null)->get(1)->getRowArray(); if(!is_array($r)){throw new NotFoundApiException('Common area bulunamadi');} $ctx=(int)(service('request')->company_id??0); if($ctx>0 && (int)$r['company_id']!==$ctx){throw new TenantAccessDeniedException('Cross-tenant erisim engellendi');} if((string)($r['status']??'')!=='active'){throw new ConflictApiException('Common area aktif degil');} return $r; }
    /** @param array<string,mixed> $area */ private function assertUnitResidentConsistency(array $area, ?int $unitId, ?int $residentId): void { $db=Database::connect(); $ctx=(int)(service('request')->company_id??0); if($unitId!==null){$u=$db->table('units')->where('id',$unitId)->where('deleted_at',null)->get(1)->getRowArray(); if(!is_array($u)){throw new NotFoundApiException('Unit bulunamadi');} if($ctx>0 && (int)$u['company_id']!==$ctx){throw new TenantAccessDeniedException('Cross-tenant erisim engellendi');} if((int)$u['site_id']!==(int)$area['site_id']){throw new ConflictApiException('unit/common_area site uyumsuz');}} if($residentId!==null){$r=$db->table('resident_profiles')->where('id',$residentId)->where('deleted_at',null)->get(1)->getRowArray(); if(!is_array($r)){throw new NotFoundApiException('Resident bulunamadi');} if($ctx>0 && (int)$r['company_id']!==$ctx){throw new TenantAccessDeniedException('Cross-tenant erisim engellendi');}} if($unitId!==null && $residentId!==null){$occ=$db->table('unit_occupancies')->where('unit_id',$unitId)->where('resident_profile_id',$residentId)->where('status','active')->where('deleted_at',null)->get(1)->getRowArray(); if(!is_array($occ)){throw new ConflictApiException('resident/unit aktif occupancy iliskisi yok');}} }
    private function assertAccessible(int $id): void { $r=Database::connect()->table('common_area_reservations')->where('id',$id)->get()->getRowArray(); if(!is_array($r)||($r['deleted_at']??null)!==null){throw new NotFoundApiException('Reservation bulunamadi');} $ctx=(int)(service('request')->company_id??0); if($ctx>0 && (int)$r['company_id']!==$ctx){throw new TenantAccessDeniedException('Cross-tenant erisim engellendi');}}
    private function generateReservationNo(): string { return 'CAR-'.date('YmdHis').'-'.strtoupper(substr(bin2hex(random_bytes(4)),0,8));}
    private function isDuplicate(Throwable $e): bool { $m=strtolower($e->getMessage()); return str_contains($m,'duplicate')||str_contains($m,'unique');}

    private function toNullableInt(array $payload, string $key): ?int
    {
        if (!array_key_exists($key, $payload) || $payload[$key] === null || $payload[$key] === '') {
            return null;
        }
        return (int) $payload[$key];
    }

    private function parseDateTime(string $value): DateTimeImmutable
    {
        $timezone = new DateTimeZone(date_default_timezone_get());
        try {
            return new DateTimeImmutable($value, $timezone);
        } catch (Throwable) {
            throw new ConflictApiException('Tarih formati gecersiz');
        }
    }

    private function lockName(int $commonAreaId): string
    {
        return 'common_area_reservation:' . $commonAreaId;
    }

    private function acquireLock(string $lockName): void
    {
        $sql = "SELECT GET_LOCK(?, 5) AS lck";
        $row = Database::connect()->query($sql, [$lockName])->getRowArray();
        if (!is_array($row) || (int) ($row['lck'] ?? 0) !== 1) {
            throw new ConflictApiException('Rezervasyon kilidi alinamadi, tekrar deneyiniz');
        }
    }

    private function releaseLock(string $lockName): void
    {
        Database::connect()->query("SELECT RELEASE_LOCK(?)", [$lockName]);
    }

    /**
     * @param array<string,mixed> $base
     */
    private function insertWithNoRetry(array $base): string
    {
        $attempt = 0;
        while ($attempt < 5) {
            $attempt++;
            $reservationNo = $this->generateReservationNo();
            try {
                $this->model->insert(array_merge($base, ['reservation_no' => $reservationNo]), true);
                return $reservationNo;
            } catch (Throwable $e) {
                if (!$this->isDuplicate($e)) {
                    throw $e;
                }
            }
        }

        throw new ConflictApiException('reservation_no uretilemedi');
    }

    /**
     * @template T
     * @param callable():T $callback
     * @return T
     */
    private function runOverlapTransaction(int $commonAreaId, string $startAt, string $endAt, ?int $excludeId, callable $callback): mixed
    {
        $db = Database::connect();
        $companyId = (int) (service('request')->company_id ?? 0);

        $db->transBegin();
        try {
            // Serialize by area row first; then lock overlapping reservation rows.
            $areaRow = $db->query(
                "SELECT id FROM common_areas WHERE id = ? AND company_id = ? AND deleted_at IS NULL FOR UPDATE",
                [$commonAreaId, $companyId]
            )->getRowArray();
            if (!is_array($areaRow)) {
                throw new NotFoundApiException('Common area bulunamadi');
            }

            $this->assertNoOverlap($commonAreaId, $startAt, $endAt, $excludeId, true);
            $result = $callback();

            if ($db->transStatus() === false) {
                throw new ConflictApiException('Rezervasyon kaydi sirasinda islem tamamlanamadi');
            }
            $db->transCommit();
            return $result;
        } catch (Throwable $e) {
            $db->transRollback();
            if ($e instanceof ConflictApiException || $e instanceof NotFoundApiException || $e instanceof TenantAccessDeniedException) {
                throw $e;
            }
            throw new ConflictApiException('Rezervasyon cakisma kontrolu sirasinda islem tamamlanamadi');
        }
    }
}
