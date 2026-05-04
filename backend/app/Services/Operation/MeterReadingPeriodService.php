<?php

namespace App\Services\Operation;

use App\Core\BaseService;
use App\Exceptions\ConflictApiException;
use App\Exceptions\NotFoundApiException;
use App\Exceptions\TenantAccessDeniedException;
use App\Libraries\ListQuery;
use App\Models\MeterReadingPeriodModel;
use Config\Database;

class MeterReadingPeriodService extends BaseService
{
    public function __construct(private readonly MeterReadingPeriodModel $model = new MeterReadingPeriodModel())
    {
        parent::__construct();
    }

    public function list(array $query): array
    {
        $q = ListQuery::normalize($query, [
            'sortable' => ['id', 'period_key', 'created_at'],
            'filterable' => ['site_id', 'status'],
        ]);
        $b = $this->model->builder()->select('*')->where('deleted_at', null);
        foreach ($q['filters'] as $field => $value) {
            $b->where($field, $value);
        }
        if ($q['search'] !== '') {
            $b->like('period_key', $q['search']);
        }
        $total = (int) $b->countAllResults(false);
        $items = $b->orderBy($q['sort'], $q['direction'])->limit($q['per_page'], ($q['page'] - 1) * $q['per_page'])->get()->getResultArray();
        return ListQuery::envelope($q['page'], $q['per_page'], $total, $items);
    }

    public function create(array $payload): array
    {
        $siteId = (int) $payload['site_id'];
        $this->assertSiteAccessible($siteId);
        $this->assertPeriodUnique($siteId, (string) $payload['period_key'], null);
        $data = [
            'site_id' => $siteId,
            'period_key' => (string) $payload['period_key'],
            'start_date' => (string) $payload['start_date'],
            'end_date' => (string) $payload['end_date'],
            'status' => (string) ($payload['status'] ?? 'draft'),
        ];
        $this->model->insert($data, true);
        $id = (int) $this->model->getInsertID();
        $created = $this->show($id);
        $this->audit('operation.meter_period.create.success', ['entity_type' => 'meter_period', 'entity_id' => $id, 'new_values' => $created]);
        return $created;
    }

    public function show(int $id): array
    {
        $this->assertAccessible($id);
        $row = $this->model->tenantFind($id);
        if (!is_array($row)) {
            throw new NotFoundApiException('Meter period bulunamadi');
        }
        return $row;
    }

    public function update(int $id, array $payload): array
    {
        $old = $this->show($id);
        if ((string) $old['status'] === 'locked') {
            throw new ConflictApiException('locked period guncellenemez');
        }
        $siteId = (int) ($payload['site_id'] ?? $old['site_id']);
        $periodKey = (string) ($payload['period_key'] ?? $old['period_key']);
        $this->assertSiteAccessible($siteId);
        $this->assertPeriodUnique($siteId, $periodKey, $id);
        $data = [];
        foreach (['site_id', 'period_key', 'start_date', 'end_date', 'status'] as $field) {
            if (array_key_exists($field, $payload)) {
                $data[$field] = $payload[$field];
            }
        }
        if ($data !== []) {
            $this->model->update($id, $data);
        }
        $new = $this->show($id);
        $this->audit('operation.meter_period.update.success', ['entity_type' => 'meter_period', 'entity_id' => $id, 'old_values' => $old, 'new_values' => $new]);
        return $new;
    }

    public function close(int $id): array
    {
        $old = $this->show($id);
        if ((string) $old['status'] === 'locked') {
            throw new ConflictApiException('locked period uzerinde close calismaz');
        }
        $this->model->update($id, ['status' => 'closed']);
        $new = $this->show($id);
        $this->audit('operation.meter_period.close.success', ['entity_type' => 'meter_period', 'entity_id' => $id, 'old_values' => $old, 'new_values' => $new]);
        return $new;
    }

    public function lock(int $id): array
    {
        $old = $this->show($id);
        $this->model->update($id, ['status' => 'locked']);
        $new = $this->show($id);
        $this->audit('operation.meter_period.lock.success', ['entity_type' => 'meter_period', 'entity_id' => $id, 'old_values' => $old, 'new_values' => $new]);
        return $new;
    }

    public function assertPeriodUsable(int $id): array
    {
        $period = $this->show($id);
        if ((string) $period['status'] === 'locked') {
            throw new ConflictApiException('locked period uzerinde islem yapilamaz');
        }
        return $period;
    }

    private function assertPeriodUnique(int $siteId, string $periodKey, ?int $exceptId): void
    {
        $b = $this->model->builder()->select('id')->where('site_id', $siteId)->where('period_key', $periodKey)->where('deleted_at', null);
        if ($exceptId !== null) {
            $b->where('id !=', $exceptId);
        }
        if ($b->get(1)->getRowArray() !== null) {
            throw new ConflictApiException('Ayni site+period_key duplicate period olamaz');
        }
    }

    private function assertSiteAccessible(int $siteId): void
    {
        $site = Database::connect()->table('sites')->where('id', $siteId)->where('deleted_at', null)->get(1)->getRowArray();
        if (!is_array($site)) {
            throw new NotFoundApiException('Site bulunamadi');
        }
        $ctx = (int) (service('request')->company_id ?? 0);
        if ($ctx > 0 && (int) $site['company_id'] !== $ctx) {
            throw new TenantAccessDeniedException('Cross-tenant erisim engellendi');
        }
    }

    private function assertAccessible(int $id): void
    {
        $row = Database::connect()->table('meter_reading_periods')->where('id', $id)->get(1)->getRowArray();
        if (!is_array($row) || ($row['deleted_at'] ?? null) !== null) {
            throw new NotFoundApiException('Meter period bulunamadi');
        }
        $ctx = (int) (service('request')->company_id ?? 0);
        if ($ctx > 0 && (int) $row['company_id'] !== $ctx) {
            throw new TenantAccessDeniedException('Cross-tenant erisim engellendi');
        }
    }
}
