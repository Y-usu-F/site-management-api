<?php

namespace App\Services\Finance;

use App\Core\BaseService;
use App\Exceptions\NotFoundApiException;
use App\Exceptions\TenantAccessDeniedException;
use App\Libraries\ListQuery;
use App\Models\PaymentEventModel;
use Config\Database;

class PaymentEventService extends BaseService
{
    public function __construct(private readonly PaymentEventModel $model = new PaymentEventModel())
    {
        parent::__construct();
    }

    public function list(array $query): array
    {
        $ctxCompanyId = (int) (service('request')->company_id ?? 0);
        $q = ListQuery::normalize($query, [
            'sortable' => ['id', 'provider', 'status', 'created_at'],
            'filterable' => ['provider', 'status', 'payment_id'],
        ]);
        $builder = $this->model->builder()->select('*')->where('deleted_at', null);
        if ($ctxCompanyId > 0) {
            $builder->where('company_id', $ctxCompanyId);
        }
        foreach ($q['filters'] as $field => $value) {
            $builder->where($field, $value);
        }
        $total = (int) $builder->countAllResults(false);
        $items = $builder->orderBy($q['sort'], $q['direction'])->limit($q['per_page'], ($q['page'] - 1) * $q['per_page'])->get()->getResultArray();
        return ListQuery::envelope($q['page'], $q['per_page'], $total, $items);
    }

    public function show(int $id): array
    {
        $this->assertAccessible($id);
        $row = $this->model->tenantFind($id);
        if (! is_array($row)) {
            throw new NotFoundApiException('Payment event bulunamadi');
        }
        return $row;
    }

    private function assertAccessible(int $id): void
    {
        $row = Database::connect()->table('payment_events')->where('id', $id)->get()->getRowArray();
        if (! is_array($row)) {
            throw new NotFoundApiException('Payment event bulunamadi');
        }
        $ctx = (int) (service('request')->company_id ?? 0);
        if ($ctx > 0 && (int) $row['company_id'] !== $ctx) {
            throw new TenantAccessDeniedException('Cross-tenant erisim engellendi');
        }
        if (($row['deleted_at'] ?? null) !== null) {
            throw new NotFoundApiException('Payment event bulunamadi');
        }
    }
}
