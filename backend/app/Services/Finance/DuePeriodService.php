<?php

namespace App\Services\Finance;

use App\Core\BaseService;
use App\Exceptions\ConflictApiException;
use App\Exceptions\NotFoundApiException;
use App\Exceptions\TenantAccessDeniedException;
use App\Libraries\ListQuery;
use App\Models\DuePeriodModel;
use Config\Database;

class DuePeriodService extends BaseService
{
    public function __construct(private readonly DuePeriodModel $model = new DuePeriodModel())
    {
        parent::__construct();
    }

    public function list(array $query): array
    {
        $q = ListQuery::normalize($query, [
            'sortable' => ['id', 'period_key', 'due_date', 'created_at'],
            'filterable' => ['site_id', 'status'],
        ]);
        $builder = $this->model->builder()->select('*')->where('deleted_at', null);
        if (! array_key_exists('status', $q['filters'])) {
            $builder->whereIn('status', ['draft', 'open']);
        }
        foreach ($q['filters'] as $field => $value) {
            $builder->where($field, $value);
        }
        $total = (int) $builder->countAllResults(false);
        $items = $builder->orderBy($q['sort'], $q['direction'])->limit($q['per_page'], ($q['page'] - 1) * $q['per_page'])->get()->getResultArray();
        return ListQuery::envelope($q['page'], $q['per_page'], $total, $items);
    }

    public function create(array $payload): array
    {
        $siteId = (int) $payload['site_id'];
        $periodKey = (string) $payload['period_key'];
        $this->assertSiteAccessible($siteId);
        $this->assertPeriodUnique($siteId, $periodKey, (string) ($payload['status'] ?? 'draft'), null);
        $data = [
            'site_id' => $siteId,
            'period_key' => $periodKey,
            'start_date' => (string) $payload['start_date'],
            'end_date' => (string) $payload['end_date'],
            'due_date' => (string) $payload['due_date'],
            'status' => (string) ($payload['status'] ?? 'draft'),
        ];
        $this->model->insert($data, true);
        $id = (int) $this->model->getInsertID();
        $created = $this->show($id);
        $this->audit('finance.due_period.create.success', ['entity_type' => 'due_period', 'entity_id' => $id, 'new_values' => $created]);
        return $created;
    }

    public function show(int $id): array
    {
        $this->assertAccessible($id);
        $row = $this->model->tenantFind($id);
        if (! is_array($row)) {
            throw new NotFoundApiException('Due period bulunamadi');
        }
        return $row;
    }

    public function update(int $id, array $payload): array
    {
        $current = $this->show($id);
        $this->assertNotLocked($current);
        $nextSiteId = array_key_exists('site_id', $payload) ? (int) $payload['site_id'] : (int) $current['site_id'];
        $nextPeriodKey = array_key_exists('period_key', $payload) ? (string) $payload['period_key'] : (string) $current['period_key'];
        $nextStatus = array_key_exists('status', $payload) ? (string) $payload['status'] : (string) $current['status'];
        $this->assertSiteAccessible($nextSiteId);
        $this->assertPeriodUnique($nextSiteId, $nextPeriodKey, $nextStatus, $id);

        $data = [];
        foreach (['site_id', 'period_key', 'start_date', 'end_date', 'due_date', 'status'] as $field) {
            if (array_key_exists($field, $payload)) {
                $data[$field] = $payload[$field];
            }
        }
        if ($data !== []) {
            $this->model->update($id, $data);
        }
        $updated = $this->show($id);
        $this->audit('finance.due_period.update.success', ['entity_type' => 'due_period', 'entity_id' => $id, 'old_values' => $current, 'new_values' => $updated]);
        return $updated;
    }

    public function delete(int $id): void
    {
        $current = $this->show($id);
        $this->assertNotLocked($current);
        $this->model->delete($id);
        $this->audit('finance.due_period.delete.success', ['entity_type' => 'due_period', 'entity_id' => $id, 'old_values' => $current]);
    }

    public function close(int $id): array
    {
        $current = $this->show($id);
        $this->assertNotLocked($current);
        $this->model->update($id, ['status' => 'closed']);
        $updated = $this->show($id);
        $this->audit('finance.due_period.close.success', ['entity_type' => 'due_period', 'entity_id' => $id, 'old_values' => $current, 'new_values' => $updated]);
        return $updated;
    }

    public function lock(int $id): array
    {
        $current = $this->show($id);
        $this->model->update($id, ['status' => 'locked']);
        $updated = $this->show($id);
        $this->audit('finance.due_period.lock.success', ['entity_type' => 'due_period', 'entity_id' => $id, 'old_values' => $current, 'new_values' => $updated]);
        return $updated;
    }

    public function assertPeriodOpenForPosting(int $id): array
    {
        $period = $this->show($id);
        if (in_array((string) $period['status'], ['closed', 'locked'], true)) {
            throw new ConflictApiException('closed/locked period icin yeni due_item olusturulamaz');
        }
        return $period;
    }

    private function assertAccessible(int $id): void
    {
        $row = Database::connect()->table('due_periods')->where('id', $id)->get()->getRowArray();
        if (! is_array($row)) {
            throw new NotFoundApiException('Due period bulunamadi');
        }
        $ctx = (int) (service('request')->company_id ?? 0);
        if ($ctx > 0 && (int) $row['company_id'] !== $ctx) {
            throw new TenantAccessDeniedException('Cross-tenant erisim engellendi');
        }
        if (($row['deleted_at'] ?? null) !== null) {
            throw new NotFoundApiException('Due period bulunamadi');
        }
    }

    private function assertSiteAccessible(int $siteId): void
    {
        $site = Database::connect()->table('sites')->where('id', $siteId)->where('deleted_at', null)->get()->getRowArray();
        if (! is_array($site)) {
            throw new NotFoundApiException('Site bulunamadi');
        }
        $ctx = (int) (service('request')->company_id ?? 0);
        if ($ctx > 0 && (int) $site['company_id'] !== $ctx) {
            throw new TenantAccessDeniedException('Cross-tenant erisim engellendi');
        }
    }

    private function assertPeriodUnique(int $siteId, string $periodKey, string $status, ?int $exceptId): void
    {
        $builder = $this->model->builder()
            ->select('id')
            ->where('site_id', $siteId)
            ->where('period_key', $periodKey)
            ->where('status', $status)
            ->where('deleted_at', null);
        if ($exceptId !== null) {
            $builder->where('id !=', $exceptId);
        }
        if ($builder->get(1)->getRowArray() !== null) {
            throw new ConflictApiException('Ayni site + period_key icin duplicate due_period engellendi');
        }
    }

    private function assertNotLocked(array $period): void
    {
        if ((string) ($period['status'] ?? '') === 'locked') {
            throw new ConflictApiException('locked due_period uzerinde islem yapilamaz');
        }
    }
}
