<?php

namespace App\Services\Resident;

use App\Core\BaseService;
use App\Exceptions\ConflictApiException;
use App\Exceptions\NotFoundApiException;
use App\Exceptions\TenantAccessDeniedException;
use App\Libraries\ListQuery;
use App\Models\UnitModel;
use App\Models\UnitOccupancyModel;
use Config\Database;

class UnitOccupancyService extends BaseService
{
    public function __construct(
        private readonly UnitOccupancyModel $occupancyModel = new UnitOccupancyModel(),
        private readonly UnitModel $unitModel = new UnitModel(),
        private readonly ResidentProfileService $residentService = new ResidentProfileService()
    ) {
        parent::__construct();
    }

    public function list(array $query): array
    {
        $q = ListQuery::normalize($query, [
            'sortable' => ['id', 'unit_id', 'resident_profile_id', 'relationship_type', 'start_date', 'created_at'],
            'filterable' => ['unit_id', 'resident_profile_id', 'relationship_type', 'status'],
        ]);

        $builder = $this->occupancyModel->builder()->select('*');
        $builder->where('deleted_at', null);
        if (! array_key_exists('status', $q['filters'])) {
            $builder->where('status', 'active');
        }
        foreach ($q['filters'] as $field => $value) {
            $builder->where($field, $value);
        }

        $total = (int) $builder->countAllResults(false);
        $items = $builder->orderBy($q['sort'], $q['direction'])
            ->limit($q['per_page'], ($q['page'] - 1) * $q['per_page'])
            ->get()->getResultArray();

        return ListQuery::envelope($q['page'], $q['per_page'], $total, $items);
    }

    public function create(array $payload): array
    {
        $unitId = (int) $payload['unit_id'];
        $residentId = (int) $payload['resident_profile_id'];
        $relationshipType = (string) $payload['relationship_type'];
        $startDate = (string) $payload['start_date'];
        $endDate = isset($payload['end_date']) ? (string) $payload['end_date'] : null;
        $isPrimary = $this->toBoolInt($payload['is_primary'] ?? false);
        $status = (string) ($payload['status'] ?? 'active');

        $db = Database::connect();
        $db->transStart();
        try {
            $this->assertDateRange($startDate, $endDate);
            $this->assertUnitActiveAndAccessible($unitId);
            $this->residentService->assertResidentIsActiveAndAccessible($residentId);
            $this->assertDuplicateActiveRelationship($unitId, $residentId, $relationshipType);
            $this->assertPrimaryUniqueness($unitId, $relationshipType, $isPrimary === 1, null);

            $this->occupancyModel->insert([
                'unit_id' => $unitId,
                'resident_profile_id' => $residentId,
                'relationship_type' => $relationshipType,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'is_primary' => $isPrimary,
                'status' => $status,
            ], true);
            $id = (int) $this->occupancyModel->getInsertID();
        } catch (NotFoundApiException|TenantAccessDeniedException $e) {
            $db->transRollback();
            throw $e;
        } catch (ConflictApiException $e) {
            $db->transRollback();
            throw $e;
        } catch (\Throwable $e) {
            $db->transRollback();
            throw new ConflictApiException('Unit occupancy kaydi olusturulamadi');
        }
        $db->transComplete();
        if (! $db->transStatus()) {
            throw new ConflictApiException('Unit occupancy kaydi olusturulamadi');
        }

        $created = $this->show($id);
        $this->audit('resident.occupancy.create.success', ['entity_type' => 'unit_occupancy', 'entity_id' => $id, 'new_values' => $created]);
        return $created;
    }

    public function show(int $id): array
    {
        $this->assertAccessibleOccupancy($id);
        $row = $this->occupancyModel->tenantFind($id);
        if (! is_array($row)) {
            throw new NotFoundApiException('Unit occupancy bulunamadi');
        }
        return $row;
    }

    public function update(int $id, array $payload): array
    {
        $current = $this->show($id);
        $data = [];
        foreach (['unit_id', 'resident_profile_id'] as $field) {
            if (array_key_exists($field, $payload)) {
                $data[$field] = (int) $payload[$field];
            }
        }
        foreach (['relationship_type', 'start_date', 'end_date', 'status'] as $field) {
            if (array_key_exists($field, $payload)) {
                $data[$field] = $payload[$field];
            }
        }
        if (array_key_exists('is_primary', $payload)) {
            $data['is_primary'] = $this->toBoolInt($payload['is_primary']);
        }

        $nextUnitId = (int) ($data['unit_id'] ?? $current['unit_id']);
        $nextResidentId = (int) ($data['resident_profile_id'] ?? $current['resident_profile_id']);
        $nextRelationshipType = (string) ($data['relationship_type'] ?? $current['relationship_type']);
        $nextStartDate = (string) ($data['start_date'] ?? $current['start_date']);
        $nextEndDate = isset($data['end_date']) ? (string) $data['end_date'] : (($current['end_date'] ?? null) !== null ? (string) $current['end_date'] : null);
        $nextIsPrimary = isset($data['is_primary']) ? (int) $data['is_primary'] : (int) $current['is_primary'];

        $this->assertDateRange($nextStartDate, $nextEndDate);
        $this->assertUnitActiveAndAccessible($nextUnitId);
        $this->residentService->assertResidentIsActiveAndAccessible($nextResidentId);
        $this->assertDuplicateActiveRelationship($nextUnitId, $nextResidentId, $nextRelationshipType, $id);
        $this->assertPrimaryUniqueness($nextUnitId, $nextRelationshipType, $nextIsPrimary === 1, $id);

        if ($data !== []) {
            $db = Database::connect();
            $db->transStart();
            try {
                $this->occupancyModel->update($id, $data);
            } catch (\Throwable $e) {
                $db->transRollback();
                throw new ConflictApiException('Unit occupancy kaydi guncellenemedi');
            }
            $db->transComplete();
            if (! $db->transStatus()) {
                throw new ConflictApiException('Unit occupancy kaydi guncellenemedi');
            }
        }
        $updated = $this->show($id);
        $this->audit('resident.occupancy.update.success', ['entity_type' => 'unit_occupancy', 'entity_id' => $id, 'old_values' => $current, 'new_values' => $updated]);
        return $updated;
    }

    public function delete(int $id): void
    {
        $current = $this->show($id);
        $this->occupancyModel->delete($id);
        $this->audit('resident.occupancy.delete.success', ['entity_type' => 'unit_occupancy', 'entity_id' => $id, 'old_values' => $current]);
    }

    private function assertUnitActiveAndAccessible(int $unitId): array
    {
        $row = Database::connect()->table('units')->where('id', $unitId)->get()->getRowArray();
        if (! is_array($row)) {
            throw new NotFoundApiException('Unit bulunamadi');
        }
        $contextCompanyId = (int) (service('request')->company_id ?? 0);
        if ($contextCompanyId > 0 && (int) $row['company_id'] !== $contextCompanyId) {
            throw new TenantAccessDeniedException('Cross-tenant erisim engellendi');
        }
        if (($row['deleted_at'] ?? null) !== null) {
            throw new NotFoundApiException('UNIT_DELETED');
        }
        if (($row['status'] ?? 'active') !== 'active') {
            throw new NotFoundApiException('Unit aktif degil');
        }
        return $row;
    }

    private function assertAccessibleOccupancy(int $id): void
    {
        $row = Database::connect()->table('unit_occupancies')->where('id', $id)->get()->getRowArray();
        if (! is_array($row)) {
            throw new NotFoundApiException('Unit occupancy bulunamadi');
        }
        $contextCompanyId = (int) (service('request')->company_id ?? 0);
        if ($contextCompanyId > 0 && (int) $row['company_id'] !== $contextCompanyId) {
            throw new TenantAccessDeniedException('Cross-tenant erisim engellendi');
        }
        if (($row['deleted_at'] ?? null) !== null) {
            throw new NotFoundApiException('Unit occupancy bulunamadi');
        }
    }

    private function assertDuplicateActiveRelationship(int $unitId, int $residentId, string $relationshipType, ?int $exceptId = null): void
    {
        $builder = $this->occupancyModel->builder()
            ->select('id')
            ->where('unit_id', $unitId)
            ->where('resident_profile_id', $residentId)
            ->where('relationship_type', $relationshipType)
            ->where('status', 'active')
            ->where('deleted_at', null);
        if ($exceptId !== null) {
            $builder->where('id !=', $exceptId);
        }
        if ($builder->get(1)->getRowArray() !== null) {
            throw new ConflictApiException('Ayni unit/resident/relationship_type aktif kaydi tekrar olusturulamaz');
        }
    }

    private function assertPrimaryUniqueness(int $unitId, string $relationshipType, bool $isPrimary, ?int $exceptId): void
    {
        if (! $isPrimary || ! in_array($relationshipType, ['owner', 'tenant'], true)) {
            return;
        }

        $builder = $this->occupancyModel->builder()
            ->select('id')
            ->where('unit_id', $unitId)
            ->where('relationship_type', $relationshipType)
            ->where('status', 'active')
            ->where('is_primary', 1)
            ->where('deleted_at', null);
        if ($exceptId !== null) {
            $builder->where('id !=', $exceptId);
        }
        if ($builder->get(1)->getRowArray() !== null) {
            throw new ConflictApiException('Unit icin birden fazla aktif primary ' . $relationshipType . ' olamaz');
        }
    }

    private function assertDateRange(string $startDate, ?string $endDate): void
    {
        if ($endDate === null || $endDate === '') {
            return;
        }
        if (strtotime($endDate) < strtotime($startDate)) {
            throw new ConflictApiException('end_date start_date degerinden kucuk olamaz');
        }
    }

    private function toBoolInt(mixed $value): int
    {
        if (is_bool($value)) {
            return $value ? 1 : 0;
        }
        return in_array((string) $value, ['1', 'true', 'TRUE'], true) ? 1 : 0;
    }
}
