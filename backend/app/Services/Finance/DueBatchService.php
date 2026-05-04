<?php

namespace App\Services\Finance;

use App\Core\BaseService;
use App\Exceptions\ConflictApiException;
use App\Exceptions\NotFoundApiException;
use App\Exceptions\TenantAccessDeniedException;
use App\Libraries\ListQuery;
use App\Models\DueBatchModel;
use App\Models\DueDefinitionModel;
use App\Models\DueItemModel;
use App\Models\UnitOccupancyModel;
use Config\Database;

class DueBatchService extends BaseService
{
    public function __construct(
        private readonly DueBatchModel $batchModel = new DueBatchModel(),
        private readonly DueDefinitionModel $definitionModel = new DueDefinitionModel(),
        private readonly DueItemModel $itemModel = new DueItemModel(),
        private readonly DuePeriodService $periodService = new DuePeriodService(),
        private readonly UnitOccupancyModel $occupancyModel = new UnitOccupancyModel()
    ) {
        parent::__construct();
    }

    public function list(array $query): array
    {
        $q = ListQuery::normalize($query, [
            'sortable' => ['id', 'status', 'created_at'],
            'filterable' => ['site_id', 'due_definition_id', 'due_period_id', 'status'],
        ]);
        $builder = $this->batchModel->builder()->select('*')->where('deleted_at', null);
        foreach ($q['filters'] as $field => $value) {
            $builder->where($field, $value);
        }
        $total = (int) $builder->countAllResults(false);
        $items = $builder->orderBy($q['sort'], $q['direction'])->limit($q['per_page'], ($q['page'] - 1) * $q['per_page'])->get()->getResultArray();
        return ListQuery::envelope($q['page'], $q['per_page'], $total, $items);
    }

    public function show(int $id): array
    {
        $this->assertAccessibleBatch($id);
        $row = $this->batchModel->tenantFind($id);
        if (! is_array($row)) {
            throw new NotFoundApiException('Due batch bulunamadi');
        }
        return $row;
    }

    public function create(array $payload): array
    {
        $definition = $this->assertDefinitionAccessible((int) $payload['due_definition_id']);
        $period = $this->periodService->assertPeriodOpenForPosting((int) $payload['due_period_id']);
        if ((int) ($definition['site_id'] ?? 0) > 0 && (int) $definition['site_id'] !== (int) $period['site_id']) {
            throw new ConflictApiException('Definition site ve period site uyumsuz');
        }

        $companyId = (int) $period['company_id'];
        $definitionId = (int) $definition['id'];
        $periodId = (int) $period['id'];
        $batchKey = $companyId . ':' . $definitionId . ':' . $periodId;

        $existing = $this->batchModel->builder()
            ->where('company_id', $companyId)
            ->where('due_definition_id', $definitionId)
            ->where('due_period_id', $periodId)
            ->where('deleted_at', null)
            ->get(1)->getRowArray();
        if ($existing !== null) {
            return $this->show((int) $existing['id']);
        }

        $units = $this->resolveTargetUnits($definition);
        $db = Database::connect();
        $db->transStart();
        $batchId = null;
        $totalAmount = 0.0;
        $totalUnits = 0;
        try {
            $this->batchModel->insert([
                'site_id' => (int) $period['site_id'],
                'due_definition_id' => $definitionId,
                'due_period_id' => $periodId,
                'batch_key' => $batchKey,
                'total_units' => 0,
                'total_amount' => 0,
                'status' => 'processing',
            ], true);
            $batchId = (int) $this->batchModel->getInsertID();

            foreach ($units as $unit) {
                $existingItem = $this->itemModel->builder()
                    ->select('id')
                    ->where('unit_id', (int) $unit['id'])
                    ->where('due_definition_id', $definitionId)
                    ->where('due_period_id', $periodId)
                    ->where('deleted_at', null)
                    ->get(1)->getRowArray();
                if ($existingItem !== null) {
                    continue;
                }

                $amount = $this->calculateAmount($definition, $unit);
                if ($amount <= 0) {
                    continue;
                }
                try {
                    $this->itemModel->insert([
                    'site_id' => (int) $unit['site_id'],
                    'block_id' => (int) $unit['block_id'],
                    'floor_id' => isset($unit['floor_id']) ? (int) $unit['floor_id'] : null,
                    'unit_id' => (int) $unit['id'],
                    'due_definition_id' => $definitionId,
                    'due_period_id' => $periodId,
                    'due_batch_id' => $batchId,
                    'description' => (string) ($definition['name'] ?? 'Aidat'),
                    'amount' => $amount,
                    'paid_amount' => 0,
                    'remaining_amount' => $amount,
                    'currency' => (string) ($definition['currency'] ?? 'TRY'),
                    'due_date' => (string) $period['due_date'],
                    'status' => 'unpaid',
                    ], true);
                } catch (\Throwable) {
                    // DB-level unique guard for idempotency: skip duplicate item insert.
                    continue;
                }
                $totalUnits++;
                $totalAmount += $amount;
            }

            $this->batchModel->update($batchId, [
                'total_units' => $totalUnits,
                'total_amount' => round($totalAmount, 2),
                'status' => 'completed',
                'error_message' => null,
            ]);
        } catch (\Throwable $e) {
            $duplicateBatch = $this->batchModel->builder()
                ->where('company_id', $companyId)
                ->where('due_definition_id', $definitionId)
                ->where('due_period_id', $periodId)
                ->where('deleted_at', null)
                ->get(1)->getRowArray();
            if ($duplicateBatch !== null) {
                $db->transRollback();
                return $this->show((int) $duplicateBatch['id']);
            }

            if ($batchId !== null) {
                $this->batchModel->update($batchId, ['status' => 'failed', 'error_message' => $e->getMessage()]);
            }
            $db->transRollback();
            $this->audit('finance.due_batch.failed', ['entity_type' => 'due_batch', 'entity_id' => $batchId, 'meta' => ['error' => $e->getMessage()]]);
            throw new ConflictApiException('Batch tahakkuk islemi basarisiz');
        }
        $db->transComplete();
        if (! $db->transStatus()) {
            throw new ConflictApiException('Batch transaction basarisiz');
        }

        $created = $this->show((int) $batchId);
        $this->audit('finance.due_batch.create.success', ['entity_type' => 'due_batch', 'entity_id' => $batchId, 'new_values' => $created]);
        $this->audit('finance.due_batch.completed', ['entity_type' => 'due_batch', 'entity_id' => $batchId, 'meta' => ['total_units' => $totalUnits, 'total_amount' => round($totalAmount, 2)]]);
        return $created;
    }

    private function assertDefinitionAccessible(int $id): array
    {
        $row = Database::connect()->table('due_definitions')->where('id', $id)->where('deleted_at', null)->get()->getRowArray();
        if (! is_array($row)) {
            throw new NotFoundApiException('Due definition bulunamadi');
        }
        $ctx = (int) (service('request')->company_id ?? 0);
        if ($ctx > 0 && (int) $row['company_id'] !== $ctx) {
            throw new TenantAccessDeniedException('Cross-tenant erisim engellendi');
        }
        return $row;
    }

    /**
     * @param array<string,mixed> $definition
     * @return list<array<string,mixed>>
     */
    private function resolveTargetUnits(array $definition): array
    {
        $builder = Database::connect()->table('units')
            ->select('units.id, units.site_id, units.block_id, units.floor_id, units.net_area, units.gross_area, units.land_share')
            ->join('sites', 'sites.id = units.site_id', 'inner')
            ->join('blocks', 'blocks.id = units.block_id', 'inner')
            ->where('units.deleted_at', null)
            ->where('units.status', 'active')
            ->where('sites.deleted_at', null)
            ->where('blocks.deleted_at', null);
        if ((int) ($definition['block_id'] ?? 0) > 0) {
            $builder->where('units.block_id', (int) $definition['block_id']);
        } elseif ((int) ($definition['site_id'] ?? 0) > 0) {
            $builder->where('units.site_id', (int) $definition['site_id']);
        } else {
            throw new ConflictApiException('Definition icin site_id veya block_id zorunlu');
        }
        return $builder->get()->getResultArray();
    }

    /**
     * @param array<string,mixed> $definition
     * @param array<string,mixed> $unit
     */
    private function calculateAmount(array $definition, array $unit): float
    {
        $base = (float) $definition['amount'];
        $calcType = (string) $definition['calculation_type'];
        if ($calcType === 'fixed') {
            return round($base, 2);
        }
        if ($calcType === 'unit_area') {
            $area = isset($unit['net_area']) && $unit['net_area'] !== null ? (float) $unit['net_area'] : (float) ($unit['gross_area'] ?? 0);
            return round($base * $area, 2);
        }
        if ($calcType === 'land_share') {
            return round($base * (float) ($unit['land_share'] ?? 0), 2);
        }

        $count = $this->occupancyModel->builder()
            ->where('unit_id', (int) $unit['id'])
            ->where('status', 'active')
            ->where('deleted_at', null)
            ->countAllResults();
        return round($base * $count, 2);
    }

    private function assertAccessibleBatch(int $id): void
    {
        $row = Database::connect()->table('due_batches')->where('id', $id)->get()->getRowArray();
        if (! is_array($row)) {
            throw new NotFoundApiException('Due batch bulunamadi');
        }
        $ctx = (int) (service('request')->company_id ?? 0);
        if ($ctx > 0 && (int) $row['company_id'] !== $ctx) {
            throw new TenantAccessDeniedException('Cross-tenant erisim engellendi');
        }
        if (($row['deleted_at'] ?? null) !== null) {
            throw new NotFoundApiException('Due batch bulunamadi');
        }
    }
}
