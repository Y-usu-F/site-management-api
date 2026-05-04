<?php

namespace App\Services\Operation;

use App\Core\BaseService;
use App\Exceptions\ConflictApiException;
use App\Exceptions\NotFoundApiException;
use App\Exceptions\TenantAccessDeniedException;
use App\Libraries\ListQuery;
use App\Models\ConsumptionReportModel;
use Config\Database;

class ConsumptionReportService extends BaseService
{
    public function __construct(
        private readonly ConsumptionReportModel $model = new ConsumptionReportModel(),
        private readonly MeterReadingService $readingService = new MeterReadingService()
    ) {
        parent::__construct();
    }

    public function list(array $query): array
    {
        $q = ListQuery::normalize($query, [
            'sortable' => ['id', 'status', 'amount', 'created_at'],
            'filterable' => ['reading_id', 'unit_id', 'status'],
        ]);
        $b = $this->model->builder()->select('*')->where('deleted_at', null);
        foreach ($q['filters'] as $field => $value) {
            $b->where($field, $value);
        }
        $total = (int) $b->countAllResults(false);
        $items = $b->orderBy($q['sort'], $q['direction'])->limit($q['per_page'], ($q['page'] - 1) * $q['per_page'])->get()->getResultArray();
        return ListQuery::envelope($q['page'], $q['per_page'], $total, $items);
    }

    public function show(int $id): array
    {
        $this->assertAccessible($id);
        $row = $this->model->tenantFind($id);
        if (!is_array($row)) {
            throw new NotFoundApiException('Consumption report bulunamadi');
        }
        return $row;
    }

    public function generateFromReading(int $readingId): array
    {
        $reading = $this->readingService->assertReadingReportable($readingId);
        $existing = $this->model->builder()->where('reading_id', $readingId)->where('deleted_at', null)->get(1)->getRowArray();
        if (is_array($existing)) {
            return $this->show((int) $existing['id']);
        }
        $meter = Database::connect()->table('meters')->where('id', (int) $reading['meter_id'])->where('deleted_at', null)->get(1)->getRowArray();
        if (!is_array($meter)) {
            throw new NotFoundApiException('Meter bulunamadi');
        }
        $unitId = (string) ($meter['scope'] ?? '') === 'unit' ? (($meter['unit_id'] ?? null) !== null ? (int) $meter['unit_id'] : null) : null;
        $amount = $reading['amount'] !== null ? (float) $reading['amount'] : 0.0;
        $this->model->insert([
            'reading_id' => $readingId,
            'unit_id' => $unitId,
            'due_item_id' => null,
            'status' => 'generated',
            'amount' => number_format($amount, 2, '.', ''),
        ], true);
        $id = (int) $this->model->getInsertID();
        $created = $this->show($id);
        $this->audit('operation.consumption_report.generate.success', ['entity_type' => 'consumption_report', 'entity_id' => $id, 'new_values' => $created]);
        return $created;
    }

    public function cancel(int $id): array
    {
        $old = $this->show($id);
        if ((string) $old['status'] === 'cancelled') {
            return $old;
        }
        $this->model->update($id, ['status' => 'cancelled']);
        $new = $this->show($id);
        $this->audit('operation.consumption_report.cancel.success', ['entity_type' => 'consumption_report', 'entity_id' => $id, 'old_values' => $old, 'new_values' => $new]);
        return $new;
    }

    private function assertAccessible(int $id): void
    {
        $row = Database::connect()->table('consumption_reports')->where('id', $id)->get(1)->getRowArray();
        if (!is_array($row) || ($row['deleted_at'] ?? null) !== null) {
            throw new NotFoundApiException('Consumption report bulunamadi');
        }
        $ctx = (int) (service('request')->company_id ?? 0);
        if ($ctx > 0 && (int) $row['company_id'] !== $ctx) {
            throw new TenantAccessDeniedException('Cross-tenant erisim engellendi');
        }
    }
}
