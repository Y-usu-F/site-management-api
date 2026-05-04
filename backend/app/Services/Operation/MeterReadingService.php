<?php

namespace App\Services\Operation;

use App\Core\BaseService;
use App\Exceptions\ConflictApiException;
use App\Exceptions\NotFoundApiException;
use App\Exceptions\TenantAccessDeniedException;
use App\Libraries\ListQuery;
use App\Models\MeterReadingModel;
use Config\Database;

class MeterReadingService extends BaseService
{
    public function __construct(
        private readonly MeterReadingModel $model = new MeterReadingModel(),
        private readonly MeterReadingPeriodService $periodService = new MeterReadingPeriodService()
    ) {
        parent::__construct();
    }

    public function list(array $query): array
    {
        $q = ListQuery::normalize($query, [
            'sortable' => ['id', 'reading_date', 'status', 'created_at'],
            'filterable' => ['meter_id', 'reading_period_id', 'source', 'status'],
        ]);
        $b = $this->model->builder()->select('*')->where('deleted_at', null);
        foreach ($q['filters'] as $field => $value) {
            $b->where($field, $value);
        }
        $total = (int) $b->countAllResults(false);
        $items = $b->orderBy($q['sort'], $q['direction'])->limit($q['per_page'], ($q['page'] - 1) * $q['per_page'])->get()->getResultArray();
        return ListQuery::envelope($q['page'], $q['per_page'], $total, $items);
    }

    public function create(array $payload): array
    {
        $meter = $this->assertMeterAccessible((int) $payload['meter_id']);
        $period = $this->periodService->assertPeriodUsable((int) $payload['reading_period_id']);
        if ((int) $meter['site_id'] !== (int) $period['site_id']) {
            throw new ConflictApiException('reading_period site_id meter.site_id ile uyusmuyor');
        }
        $this->assertActiveReadingUnique((int) $meter['id'], (int) $period['id'], null);
        $data = $this->normalizeReadingPayload($payload);
        $this->applyBusinessRules($data, true);
        $this->model->insert($data, true);
        $id = (int) $this->model->getInsertID();
        $created = $this->show($id);
        $this->audit('operation.meter_reading.create.success', ['entity_type' => 'meter_reading', 'entity_id' => $id, 'new_values' => $created]);
        return $created;
    }

    public function show(int $id): array
    {
        $this->assertAccessible($id);
        $row = $this->model->tenantFind($id);
        if (!is_array($row)) {
            throw new NotFoundApiException('Meter reading bulunamadi');
        }
        return $row;
    }

    public function update(int $id, array $payload): array
    {
        $old = $this->show($id);
        if (in_array((string) $old['status'], ['approved', 'cancelled'], true)) {
            throw new ConflictApiException('approved/cancelled reading guncellenemez');
        }
        $meter = $this->assertMeterAccessible((int) $old['meter_id']);
        $period = $this->periodService->assertPeriodUsable((int) $old['reading_period_id']);
        if ((int) $meter['site_id'] !== (int) $period['site_id']) {
            throw new ConflictApiException('reading_period site_id meter.site_id ile uyusmuyor');
        }
        $data = $this->normalizeReadingPayload($payload, $old);
        $this->applyBusinessRules($data, false);
        $this->assertActiveReadingUnique((int) $old['meter_id'], (int) $old['reading_period_id'], $id);
        $this->model->update($id, $data);
        $new = $this->show($id);
        $this->audit('operation.meter_reading.update.success', ['entity_type' => 'meter_reading', 'entity_id' => $id, 'old_values' => $old, 'new_values' => $new]);
        return $new;
    }

    public function approve(int $id): array
    {
        $old = $this->show($id);
        if ((string) $old['status'] !== 'pending') {
            throw new ConflictApiException('approve sadece pending icin calisir');
        }
        $this->periodService->assertPeriodUsable((int) $old['reading_period_id']);
        $this->assertActiveReadingUnique((int) $old['meter_id'], (int) $old['reading_period_id'], $id);
        $this->model->update($id, ['status' => 'approved', 'approved_by_user_id' => service('request')->user?->id ?? null, 'approved_at' => date('Y-m-d H:i:s')]);
        $new = $this->show($id);
        $this->audit('operation.meter_reading.approve.success', ['entity_type' => 'meter_reading', 'entity_id' => $id, 'old_values' => $old, 'new_values' => $new]);
        return $new;
    }

    public function reject(int $id, ?string $reason): array
    {
        $old = $this->show($id);
        if ((string) $old['status'] !== 'pending') {
            throw new ConflictApiException('reject sadece pending icin calisir');
        }
        $this->periodService->assertPeriodUsable((int) $old['reading_period_id']);
        $this->model->update($id, ['status' => 'rejected', 'rejected_reason' => $reason]);
        $new = $this->show($id);
        $this->audit('operation.meter_reading.reject.success', ['entity_type' => 'meter_reading', 'entity_id' => $id, 'old_values' => $old, 'new_values' => $new]);
        return $new;
    }

    public function cancel(int $id): array
    {
        $old = $this->show($id);
        if (!in_array((string) $old['status'], ['pending', 'approved'], true)) {
            throw new ConflictApiException('cancel pending/approved icin calisir');
        }
        $this->periodService->assertPeriodUsable((int) $old['reading_period_id']);
        $this->model->update($id, ['status' => 'cancelled']);
        $new = $this->show($id);
        $this->audit('operation.meter_reading.cancel.success', ['entity_type' => 'meter_reading', 'entity_id' => $id, 'old_values' => $old, 'new_values' => $new]);
        return $new;
    }

    /** @return array<string,mixed> */
    public function assertReadingReportable(int $id): array
    {
        $reading = $this->show($id);
        if ((string) $reading['status'] !== 'approved') {
            throw new ConflictApiException('consumption report sadece approved reading icin uretilir');
        }
        if ((string) $reading['status'] === 'cancelled') {
            throw new ConflictApiException('cancelled reading icin report uretilmez');
        }
        return $reading;
    }

    /** @return array<string,mixed> */
    private function normalizeReadingPayload(array $payload, array $current = []): array
    {
        return [
            'meter_id' => (int) ($payload['meter_id'] ?? $current['meter_id'] ?? 0),
            'reading_period_id' => (int) ($payload['reading_period_id'] ?? $current['reading_period_id'] ?? 0),
            'previous_index' => (string) ($payload['previous_index'] ?? $current['previous_index'] ?? '0'),
            'current_index' => (string) ($payload['current_index'] ?? $current['current_index'] ?? '0'),
            'unit_price' => array_key_exists('unit_price', $payload) ? ($payload['unit_price'] === null || $payload['unit_price'] === '' ? null : (string) $payload['unit_price']) : ($current['unit_price'] ?? null),
            'reading_date' => (string) ($payload['reading_date'] ?? $current['reading_date'] ?? date('Y-m-d')),
            'source' => (string) ($payload['source'] ?? $current['source'] ?? 'admin'),
            'status' => (string) ($payload['status'] ?? $current['status'] ?? 'pending'),
            'submitted_by_user_id' => $current['submitted_by_user_id'] ?? (service('request')->user?->id ?? null),
            'approved_by_user_id' => $current['approved_by_user_id'] ?? null,
            'approved_at' => $current['approved_at'] ?? null,
            'rejected_reason' => $current['rejected_reason'] ?? null,
            'photo_path' => array_key_exists('photo_path', $payload) ? (($payload['photo_path'] ?? '') === '' ? null : (string) $payload['photo_path']) : ($current['photo_path'] ?? null),
        ];
    }

    /** @param array<string,mixed> $data */
    private function applyBusinessRules(array &$data, bool $isCreate): void
    {
        if ((float) $data['current_index'] < (float) $data['previous_index']) {
            throw new ConflictApiException('current_index previous_indexten kucuk olamaz');
        }
        $consumption = round((float) $data['current_index'] - (float) $data['previous_index'], 3);
        $data['consumption'] = number_format($consumption, 3, '.', '');
        if ($data['unit_price'] !== null) {
            $data['amount'] = number_format(round($consumption * (float) $data['unit_price'], 2), 2, '.', '');
        } else {
            $data['amount'] = null;
        }
        if ($data['source'] === 'resident') {
            $data['status'] = 'pending';
        }
        if ($isCreate && in_array((string) $data['source'], ['admin', 'import'], true) && !in_array((string) $data['status'], ['pending', 'approved'], true)) {
            throw new ConflictApiException('admin/import source icin status pending veya approved olmali');
        }
    }

    private function assertActiveReadingUnique(int $meterId, int $periodId, ?int $exceptId): void
    {
        $b = $this->model->builder()
            ->select('id')
            ->where('meter_id', $meterId)
            ->where('reading_period_id', $periodId)
            ->whereIn('status', ['pending', 'approved'])
            ->where('deleted_at', null);
        if ($exceptId !== null) {
            $b->where('id !=', $exceptId);
        }
        if ($b->get(1)->getRowArray() !== null) {
            throw new ConflictApiException('Ayni meter+period icin tek aktif reading olabilir');
        }
    }

    /** @return array<string,mixed> */
    private function assertMeterAccessible(int $id): array
    {
        $row = Database::connect()->table('meters')->where('id', $id)->where('deleted_at', null)->get(1)->getRowArray();
        if (!is_array($row)) {
            throw new NotFoundApiException('Meter bulunamadi');
        }
        $ctx = (int) (service('request')->company_id ?? 0);
        if ($ctx > 0 && (int) $row['company_id'] !== $ctx) {
            throw new TenantAccessDeniedException('Cross-tenant erisim engellendi');
        }
        return $row;
    }

    private function assertAccessible(int $id): void
    {
        $row = Database::connect()->table('meter_readings')->where('id', $id)->get(1)->getRowArray();
        if (!is_array($row) || ($row['deleted_at'] ?? null) !== null) {
            throw new NotFoundApiException('Meter reading bulunamadi');
        }
        $ctx = (int) (service('request')->company_id ?? 0);
        if ($ctx > 0 && (int) $row['company_id'] !== $ctx) {
            throw new TenantAccessDeniedException('Cross-tenant erisim engellendi');
        }
    }
}
