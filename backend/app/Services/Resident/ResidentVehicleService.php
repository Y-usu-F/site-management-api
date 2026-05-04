<?php

namespace App\Services\Resident;

use App\Core\BaseService;
use App\Exceptions\ConflictApiException;
use App\Exceptions\NotFoundApiException;
use App\Exceptions\TenantAccessDeniedException;
use App\Libraries\ListQuery;
use App\Models\ResidentVehicleModel;
use App\Models\UnitModel;
use Config\Database;

class ResidentVehicleService extends BaseService
{
    public function __construct(
        private readonly ResidentVehicleModel $vehicleModel = new ResidentVehicleModel(),
        private readonly ResidentProfileService $residentService = new ResidentProfileService(),
        private readonly UnitModel $unitModel = new UnitModel()
    ) {
        parent::__construct();
    }

    public function list(array $query): array
    {
        $q = ListQuery::normalize($query, [
            'sortable' => ['id', 'resident_profile_id', 'plate_number', 'created_at'],
            'filterable' => ['resident_profile_id', 'unit_id', 'status'],
        ]);

        $builder = $this->vehicleModel->builder()->select('*');
        $builder->where('deleted_at', null);
        if (! array_key_exists('status', $q['filters'])) {
            $builder->where('status', 'active');
        }
        if ($q['search'] !== '') {
            $builder->groupStart()->like('plate_number', $q['search'])->orLike('brand', $q['search'])->orLike('model', $q['search'])->groupEnd();
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
        $residentId = (int) $payload['resident_profile_id'];
        $unitId = isset($payload['unit_id']) ? (int) $payload['unit_id'] : null;
        $plate = $this->normalizePlate((string) $payload['plate_number']);
        $this->residentService->assertResidentIsActiveAndAccessible($residentId);
        if ($unitId !== null) {
            $this->assertUnitActiveAndAccessible($unitId);
        }
        $this->assertActivePlateUnique($plate, null);

        $data = [
            'resident_profile_id' => $residentId,
            'unit_id' => $unitId,
            'plate_number' => $plate,
            'brand' => isset($payload['brand']) ? trim((string) $payload['brand']) : null,
            'model' => isset($payload['model']) ? trim((string) $payload['model']) : null,
            'color' => isset($payload['color']) ? trim((string) $payload['color']) : null,
            'parking_slot' => isset($payload['parking_slot']) ? trim((string) $payload['parking_slot']) : null,
            'status' => (string) ($payload['status'] ?? 'active'),
        ];
        $this->vehicleModel->insert($data, true);
        $id = (int) $this->vehicleModel->getInsertID();
        $created = $this->show($id);
        $this->audit('resident.vehicle.create.success', ['entity_type' => 'resident_vehicle', 'entity_id' => $id, 'new_values' => $created]);
        return $created;
    }

    public function show(int $id): array
    {
        $this->assertAccessibleVehicle($id);
        $row = $this->vehicleModel->tenantFind($id);
        if (! is_array($row)) {
            throw new NotFoundApiException('Resident vehicle bulunamadi');
        }
        return $row;
    }

    public function update(int $id, array $payload): array
    {
        $current = $this->show($id);
        $data = [];
        if (array_key_exists('resident_profile_id', $payload)) {
            $data['resident_profile_id'] = (int) $payload['resident_profile_id'];
        }
        if (array_key_exists('unit_id', $payload)) {
            $data['unit_id'] = $payload['unit_id'] !== null ? (int) $payload['unit_id'] : null;
        }
        foreach (['brand', 'model', 'color', 'parking_slot', 'status'] as $field) {
            if (array_key_exists($field, $payload)) {
                $data[$field] = trim((string) $payload[$field]);
            }
        }
        if (array_key_exists('plate_number', $payload)) {
            $data['plate_number'] = $this->normalizePlate((string) $payload['plate_number']);
        }

        $residentId = (int) ($data['resident_profile_id'] ?? $current['resident_profile_id']);
        $unitId = array_key_exists('unit_id', $data) ? $data['unit_id'] : ($current['unit_id'] !== null ? (int) $current['unit_id'] : null);
        $plate = (string) ($data['plate_number'] ?? $current['plate_number']);
        $this->residentService->assertResidentIsActiveAndAccessible($residentId);
        if ($unitId !== null) {
            $this->assertUnitActiveAndAccessible((int) $unitId);
        }
        $this->assertActivePlateUnique($plate, $id);

        if ($data !== []) {
            $this->vehicleModel->update($id, $data);
        }
        $updated = $this->show($id);
        $this->audit('resident.vehicle.update.success', ['entity_type' => 'resident_vehicle', 'entity_id' => $id, 'old_values' => $current, 'new_values' => $updated]);
        return $updated;
    }

    public function delete(int $id): void
    {
        $current = $this->show($id);
        $this->vehicleModel->delete($id);
        $this->audit('resident.vehicle.delete.success', ['entity_type' => 'resident_vehicle', 'entity_id' => $id, 'old_values' => $current]);
    }

    private function normalizePlate(string $plate): string
    {
        return strtoupper((string) preg_replace('/\s+/', '', trim($plate)));
    }

    private function assertUnitActiveAndAccessible(int $unitId): void
    {
        $row = Database::connect()->table('units')->where('id', $unitId)->get()->getRowArray();
        if (! is_array($row)) {
            throw new NotFoundApiException('Unit bulunamadi');
        }
        $contextCompanyId = (int) (service('request')->company_id ?? 0);
        if ($contextCompanyId > 0 && (int) $row['company_id'] !== $contextCompanyId) {
            throw new TenantAccessDeniedException('Cross-tenant erisim engellendi');
        }
        if (($row['deleted_at'] ?? null) !== null || ($row['status'] ?? 'active') !== 'active') {
            throw new NotFoundApiException('Unit aktif degil');
        }
    }

    private function assertActivePlateUnique(string $plate, ?int $exceptId): void
    {
        $builder = $this->vehicleModel->builder()
            ->select('id')
            ->where('plate_number', $plate)
            ->where('status', 'active')
            ->where('deleted_at', null);
        if ($exceptId !== null) {
            $builder->where('id !=', $exceptId);
        }
        if ($builder->get(1)->getRowArray() !== null) {
            throw new ConflictApiException('Ayni tenant icinde aktif plate_number tekrar eklenemez');
        }
    }

    private function assertAccessibleVehicle(int $id): void
    {
        $row = Database::connect()->table('resident_vehicles')->where('id', $id)->get()->getRowArray();
        if (! is_array($row)) {
            throw new NotFoundApiException('Resident vehicle bulunamadi');
        }
        $contextCompanyId = (int) (service('request')->company_id ?? 0);
        if ($contextCompanyId > 0 && (int) $row['company_id'] !== $contextCompanyId) {
            throw new TenantAccessDeniedException('Cross-tenant erisim engellendi');
        }
        if (($row['deleted_at'] ?? null) !== null) {
            throw new NotFoundApiException('Resident vehicle bulunamadi');
        }
    }
}
