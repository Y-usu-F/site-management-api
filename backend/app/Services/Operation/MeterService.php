<?php

namespace App\Services\Operation;

use App\Core\BaseService;
use App\Exceptions\ConflictApiException;
use App\Exceptions\NotFoundApiException;
use App\Exceptions\TenantAccessDeniedException;
use App\Libraries\ListQuery;
use App\Models\MeterModel;
use Config\Database;

class MeterService extends BaseService
{
    public function __construct(private readonly MeterModel $model = new MeterModel())
    {
        parent::__construct();
    }

    public function list(array $query): array
    {
        $q = ListQuery::normalize($query, [
            'sortable' => ['id', 'meter_no', 'meter_type', 'scope', 'created_at'],
            'filterable' => ['site_id', 'block_id', 'unit_id', 'scope', 'status', 'meter_type'],
        ]);
        $b = $this->model->builder()->select('*')->where('deleted_at', null);
        foreach ($q['filters'] as $field => $value) {
            $b->where($field, $value);
        }
        if ($q['search'] !== '') {
            $b->groupStart()->like('meter_no', $q['search'])->orLike('name', $q['search'])->groupEnd();
        }
        $total = (int) $b->countAllResults(false);
        $items = $b->orderBy($q['sort'], $q['direction'])->limit($q['per_page'], ($q['page'] - 1) * $q['per_page'])->get()->getResultArray();
        return ListQuery::envelope($q['page'], $q['per_page'], $total, $items);
    }

    public function create(array $payload): array
    {
        $data = $this->normalizePayload($payload);
        $this->assertScope($data['scope'], $data['site_id'], $data['block_id'], $data['unit_id']);
        $this->assertSiteBlockUnitAccessible($data['site_id'], $data['block_id'], $data['unit_id']);
        $this->assertMeterNoUnique($data['meter_no'], null);
        $this->model->insert($data, true);
        $id = (int) $this->model->getInsertID();
        $created = $this->show($id);
        $this->audit('operation.meter.create.success', ['entity_type' => 'meter', 'entity_id' => $id, 'new_values' => $created]);
        return $created;
    }

    public function show(int $id): array
    {
        $this->assertAccessible($id);
        $row = $this->model->tenantFind($id);
        if (!is_array($row)) {
            throw new NotFoundApiException('Meter bulunamadi');
        }
        return $row;
    }

    public function update(int $id, array $payload): array
    {
        $old = $this->show($id);
        $data = $this->normalizePayload($payload, $old);
        $this->assertScope($data['scope'], $data['site_id'], $data['block_id'], $data['unit_id']);
        $this->assertSiteBlockUnitAccessible($data['site_id'], $data['block_id'], $data['unit_id']);
        $this->assertMeterNoUnique($data['meter_no'], $id);
        $this->model->update($id, $data);
        $new = $this->show($id);
        $this->audit('operation.meter.update.success', ['entity_type' => 'meter', 'entity_id' => $id, 'old_values' => $old, 'new_values' => $new]);
        return $new;
    }

    public function delete(int $id): void
    {
        $old = $this->show($id);
        $this->model->delete($id);
        $this->audit('operation.meter.delete.success', ['entity_type' => 'meter', 'entity_id' => $id, 'old_values' => $old]);
    }

    /** @return array<string,mixed> */
    private function normalizePayload(array $payload, array $current = []): array
    {
        return [
            'site_id' => (int) ($payload['site_id'] ?? $current['site_id'] ?? 0),
            'block_id' => array_key_exists('block_id', $payload) ? ($payload['block_id'] === null || $payload['block_id'] === '' ? null : (int) $payload['block_id']) : ($current['block_id'] ?? null),
            'unit_id' => array_key_exists('unit_id', $payload) ? ($payload['unit_id'] === null || $payload['unit_id'] === '' ? null : (int) $payload['unit_id']) : ($current['unit_id'] ?? null),
            'meter_no' => array_key_exists('meter_no', $payload) ? (($payload['meter_no'] ?? '') === '' ? null : trim((string) $payload['meter_no'])) : ($current['meter_no'] ?? null),
            'meter_type' => (string) ($payload['meter_type'] ?? $current['meter_type'] ?? ''),
            'scope' => (string) ($payload['scope'] ?? $current['scope'] ?? ''),
            'name' => array_key_exists('name', $payload) ? (($payload['name'] ?? '') === '' ? null : trim((string) $payload['name'])) : ($current['name'] ?? null),
            'status' => (string) ($payload['status'] ?? $current['status'] ?? 'active'),
        ];
    }

    private function assertScope(string $scope, int $siteId, ?int $blockId, ?int $unitId): void
    {
        if ($siteId <= 0) {
            throw new ConflictApiException('site_id zorunludur');
        }
        if ($scope === 'site' && ($blockId !== null || $unitId !== null)) {
            throw new ConflictApiException('scope=site icin block_id/unit_id null olmali');
        }
        if ($scope === 'block' && ($blockId === null || $unitId !== null)) {
            throw new ConflictApiException('scope=block icin block_id zorunlu ve unit_id null olmali');
        }
        if ($scope === 'unit' && ($blockId === null || $unitId === null)) {
            throw new ConflictApiException('scope=unit icin block_id ve unit_id zorunlu');
        }
    }

    private function assertSiteBlockUnitAccessible(int $siteId, ?int $blockId, ?int $unitId): void
    {
        $db = Database::connect();
        $ctx = (int) (service('request')->company_id ?? 0);
        $site = $db->table('sites')->where('id', $siteId)->where('deleted_at', null)->get(1)->getRowArray();
        if (!is_array($site)) {
            throw new NotFoundApiException('Site bulunamadi');
        }
        if ($ctx > 0 && (int) $site['company_id'] !== $ctx) {
            throw new TenantAccessDeniedException('Cross-tenant erisim engellendi');
        }
        if ($blockId !== null) {
            $block = $db->table('blocks')->where('id', $blockId)->where('deleted_at', null)->get(1)->getRowArray();
            if (!is_array($block)) {
                throw new NotFoundApiException('Block bulunamadi');
            }
            if ((int) $block['site_id'] !== $siteId) {
                throw new ConflictApiException('block/site uyumsuz');
            }
            if ($ctx > 0 && (int) $block['company_id'] !== $ctx) {
                throw new TenantAccessDeniedException('Cross-tenant erisim engellendi');
            }
        }
        if ($unitId !== null) {
            $unit = $db->table('units')->where('id', $unitId)->where('deleted_at', null)->get(1)->getRowArray();
            if (!is_array($unit)) {
                throw new NotFoundApiException('Unit bulunamadi');
            }
            if ((int) $unit['site_id'] !== $siteId || ($blockId !== null && (int) $unit['block_id'] !== $blockId)) {
                throw new ConflictApiException('unit/block/site uyumsuz');
            }
            if ($ctx > 0 && (int) $unit['company_id'] !== $ctx) {
                throw new TenantAccessDeniedException('Cross-tenant erisim engellendi');
            }
        }
    }

    private function assertMeterNoUnique(?string $meterNo, ?int $exceptId): void
    {
        if ($meterNo === null || $meterNo === '') {
            return;
        }
        $b = $this->model->builder()->select('id')->where('meter_no', $meterNo)->where('deleted_at', null);
        if ($exceptId !== null) {
            $b->where('id !=', $exceptId);
        }
        if ($b->get(1)->getRowArray() !== null) {
            throw new ConflictApiException('meter_no ayni tenant icinde unique olmali');
        }
    }

    private function assertAccessible(int $id): void
    {
        $row = Database::connect()->table('meters')->where('id', $id)->get(1)->getRowArray();
        if (!is_array($row) || ($row['deleted_at'] ?? null) !== null) {
            throw new NotFoundApiException('Meter bulunamadi');
        }
        $ctx = (int) (service('request')->company_id ?? 0);
        if ($ctx > 0 && (int) $row['company_id'] !== $ctx) {
            throw new TenantAccessDeniedException('Cross-tenant erisim engellendi');
        }
    }
}
