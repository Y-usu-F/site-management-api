<?php

namespace App\Services\Resident;

use App\Core\BaseService;
use App\Exceptions\NotFoundApiException;
use App\Exceptions\TenantAccessDeniedException;
use App\Libraries\ListQuery;
use App\Models\ResidentContactModel;
use Config\Database;

class ResidentContactService extends BaseService
{
    public function __construct(
        private readonly ResidentContactModel $contactModel = new ResidentContactModel(),
        private readonly ResidentProfileService $residentService = new ResidentProfileService()
    ) {
        parent::__construct();
    }

    public function list(array $query): array
    {
        $q = ListQuery::normalize($query, [
            'sortable' => ['id', 'resident_profile_id', 'type', 'created_at'],
            'filterable' => ['resident_profile_id', 'type'],
        ]);

        $builder = $this->contactModel->builder()->select('*');
        $builder->where('deleted_at', null);
        if ($q['search'] !== '') {
            $builder->groupStart()->like('value', $q['search'])->orLike('label', $q['search'])->groupEnd();
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
        $type = (string) $payload['type'];
        $isPrimary = $this->toBoolInt($payload['is_primary'] ?? false);
        $this->residentService->assertResidentIsActiveAndAccessible($residentId);

        $data = [
            'resident_profile_id' => $residentId,
            'type' => $type,
            'label' => isset($payload['label']) ? trim((string) $payload['label']) : null,
            'value' => trim((string) $payload['value']),
            'is_primary' => $isPrimary,
        ];

        $this->contactModel->insert($data, true);
        $id = (int) $this->contactModel->getInsertID();
        if ($isPrimary === 1) {
            $this->dropOtherPrimary($residentId, $type, $id);
        }
        $created = $this->show($id);
        $this->audit('resident.contact.create.success', ['entity_type' => 'resident_contact', 'entity_id' => $id, 'new_values' => $created]);
        return $created;
    }

    public function show(int $id): array
    {
        $this->assertAccessibleContact($id);
        $row = $this->contactModel->tenantFind($id);
        if (! is_array($row)) {
            throw new NotFoundApiException('Resident contact bulunamadi');
        }
        return $row;
    }

    public function update(int $id, array $payload): array
    {
        $current = $this->show($id);
        $data = [];
        foreach (['resident_profile_id'] as $field) {
            if (array_key_exists($field, $payload)) {
                $data[$field] = (int) $payload[$field];
            }
        }
        foreach (['type', 'label', 'value'] as $field) {
            if (array_key_exists($field, $payload)) {
                $data[$field] = trim((string) $payload[$field]);
            }
        }
        if (array_key_exists('is_primary', $payload)) {
            $data['is_primary'] = $this->toBoolInt($payload['is_primary']);
        }

        $residentId = (int) ($data['resident_profile_id'] ?? $current['resident_profile_id']);
        $type = (string) ($data['type'] ?? $current['type']);
        $isPrimary = (int) ($data['is_primary'] ?? $current['is_primary']);
        $this->residentService->assertResidentIsActiveAndAccessible($residentId);

        if ($data !== []) {
            $this->contactModel->update($id, $data);
        }
        if ($isPrimary === 1) {
            $this->dropOtherPrimary($residentId, $type, $id);
        }
        $updated = $this->show($id);
        $this->audit('resident.contact.update.success', ['entity_type' => 'resident_contact', 'entity_id' => $id, 'old_values' => $current, 'new_values' => $updated]);
        return $updated;
    }

    public function delete(int $id): void
    {
        $current = $this->show($id);
        $this->contactModel->delete($id);
        $this->audit('resident.contact.delete.success', ['entity_type' => 'resident_contact', 'entity_id' => $id, 'old_values' => $current]);
    }

    private function assertAccessibleContact(int $id): void
    {
        $row = Database::connect()->table('resident_contacts')->where('id', $id)->get()->getRowArray();
        if (! is_array($row)) {
            throw new NotFoundApiException('Resident contact bulunamadi');
        }
        $contextCompanyId = (int) (service('request')->company_id ?? 0);
        if ($contextCompanyId > 0 && (int) $row['company_id'] !== $contextCompanyId) {
            throw new TenantAccessDeniedException('Cross-tenant erisim engellendi');
        }
        if (($row['deleted_at'] ?? null) !== null) {
            throw new NotFoundApiException('Resident contact bulunamadi');
        }
    }

    private function dropOtherPrimary(int $residentId, string $type, int $exceptId): void
    {
        $this->contactModel->builder()
            ->where('resident_profile_id', $residentId)
            ->where('type', $type)
            ->where('id !=', $exceptId)
            ->where('deleted_at', null)
            ->update(['is_primary' => 0, 'updated_at' => date('Y-m-d H:i:s')]);
    }

    private function toBoolInt(mixed $value): int
    {
        if (is_bool($value)) {
            return $value ? 1 : 0;
        }
        return in_array((string) $value, ['1', 'true', 'TRUE'], true) ? 1 : 0;
    }
}
