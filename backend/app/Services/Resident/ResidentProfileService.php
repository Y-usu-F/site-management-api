<?php

namespace App\Services\Resident;

use App\Core\BaseService;
use App\Exceptions\NotFoundApiException;
use App\Exceptions\TenantAccessDeniedException;
use App\Libraries\ListQuery;
use App\Models\ResidentProfileModel;
use Config\Database;

class ResidentProfileService extends BaseService
{
    public function __construct(private readonly ResidentProfileModel $residentModel = new ResidentProfileModel())
    {
        parent::__construct();
    }

    public function list(array $query): array
    {
        $q = ListQuery::normalize($query, [
            'sortable' => ['id', 'first_name', 'last_name', 'created_at'],
            'filterable' => ['status'],
            'default_sort' => 'id',
            'default_direction' => 'desc',
        ]);

        $builder = $this->residentModel->builder()->select('*');
        $builder->where('deleted_at', null);
        if (! array_key_exists('status', $q['filters'])) {
            $builder->where('status', 'active');
        }
        if ($q['search'] !== '') {
            $builder->groupStart()
                ->like('first_name', $q['search'])
                ->orLike('last_name', $q['search'])
                ->orLike('identity_number', $q['search'])
                ->groupEnd();
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
        $data = [
            'company_id' => isset($payload['company_id']) ? (int) $payload['company_id'] : null,
            'user_id' => $payload['user_id'] ?? null,
            'first_name' => trim((string) $payload['first_name']),
            'last_name' => trim((string) $payload['last_name']),
            'identity_number' => isset($payload['identity_number']) ? trim((string) $payload['identity_number']) : null,
            'phone' => isset($payload['phone']) ? trim((string) $payload['phone']) : null,
            'email' => isset($payload['email']) ? strtolower(trim((string) $payload['email'])) : null,
            'birth_date' => $payload['birth_date'] ?? null,
            'status' => (string) ($payload['status'] ?? 'active'),
        ];

        $this->residentModel->insert($data, true);
        $id = (int) $this->residentModel->getInsertID();
        $created = $this->show($id);
        $this->audit('resident.profile.create.success', ['entity_type' => 'resident_profile', 'entity_id' => $id, 'new_values' => $created]);
        return $created;
    }

    public function show(int $id): array
    {
        $this->assertAccessibleResident($id);
        $row = $this->residentModel->tenantFind($id);
        if (! is_array($row)) {
            throw new NotFoundApiException('Resident profile bulunamadi');
        }
        return $row;
    }

    public function update(int $id, array $payload): array
    {
        $current = $this->show($id);
        $data = [];
        foreach (['user_id', 'birth_date'] as $field) {
            if (array_key_exists($field, $payload)) {
                $data[$field] = $payload[$field];
            }
        }
        foreach (['first_name', 'last_name', 'identity_number', 'phone', 'status'] as $field) {
            if (array_key_exists($field, $payload)) {
                $data[$field] = trim((string) $payload[$field]);
            }
        }
        if (array_key_exists('email', $payload)) {
            $data['email'] = strtolower(trim((string) $payload['email']));
        }

        if ($data !== []) {
            $this->residentModel->update($id, $data);
        }
        $updated = $this->show($id);
        $this->audit('resident.profile.update.success', ['entity_type' => 'resident_profile', 'entity_id' => $id, 'old_values' => $current, 'new_values' => $updated]);
        return $updated;
    }

    public function delete(int $id): void
    {
        $current = $this->show($id);
        $this->residentModel->delete($id);
        $this->audit('resident.profile.delete.success', ['entity_type' => 'resident_profile', 'entity_id' => $id, 'old_values' => $current]);
    }

    public function assertResidentIsActiveAndAccessible(int $id): array
    {
        $this->assertAccessibleResident($id);
        $row = $this->residentModel->tenantFind($id);
        if (! is_array($row) || ($row['status'] ?? 'active') !== 'active') {
            throw new NotFoundApiException('Resident profile aktif degil');
        }
        return $row;
    }

    private function assertAccessibleResident(int $id): void
    {
        $row = Database::connect()->table('resident_profiles')->where('id', $id)->get()->getRowArray();
        if (! is_array($row)) {
            throw new NotFoundApiException('Resident profile bulunamadi');
        }
        $contextCompanyId = (int) (service('request')->company_id ?? 0);
        if ($contextCompanyId > 0 && (int) $row['company_id'] !== $contextCompanyId) {
            throw new TenantAccessDeniedException('Cross-tenant erisim engellendi');
        }
        if (($row['deleted_at'] ?? null) !== null) {
            throw new NotFoundApiException('Resident profile bulunamadi');
        }
    }
}
