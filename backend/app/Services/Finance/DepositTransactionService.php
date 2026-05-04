<?php

namespace App\Services\Finance;

use App\Core\BaseService;
use App\Exceptions\NotFoundApiException;
use App\Exceptions\TenantAccessDeniedException;
use App\Libraries\ListQuery;
use App\Models\DepositTransactionModel;
use Config\Database;

class DepositTransactionService extends BaseService
{
    public function __construct(
        private readonly DepositTransactionModel $model = new DepositTransactionModel(),
        private readonly DepositService $depositService = new DepositService()
    ) {
        parent::__construct();
    }

    public function listByDeposit(int $depositId, array $query = []): array
    {
        $this->depositService->show($depositId);
        $q = ListQuery::normalize($query, [
            'sortable' => ['id', 'transaction_type', 'amount', 'transaction_date', 'created_at'],
            'filterable' => ['transaction_type', 'currency', 'due_item_id', 'payment_id'],
        ]);
        $builder = $this->model->builder()->select('*')->where('deposit_id', $depositId)->where('deleted_at', null);
        foreach ($q['filters'] as $field => $value) {
            $builder->where($field, $value);
        }
        if ($q['search'] !== '') {
            $builder->groupStart()->like('description', $q['search'])->groupEnd();
        }
        $total = (int) $builder->countAllResults(false);
        $items = $builder->orderBy($q['sort'], $q['direction'])->limit($q['per_page'], ($q['page'] - 1) * $q['per_page'])->get()->getResultArray();
        return ListQuery::envelope($q['page'], $q['per_page'], $total, $items);
    }

    public function show(int $id): array
    {
        $row = Database::connect()->table('deposit_transactions')->where('id', $id)->get(1)->getRowArray();
        if (! is_array($row) || ($row['deleted_at'] ?? null) !== null) {
            throw new NotFoundApiException('Deposit transaction bulunamadi');
        }
        $ctx = (int) (service('request')->company_id ?? 0);
        if ($ctx > 0 && (int) $row['company_id'] !== $ctx) {
            throw new TenantAccessDeniedException('Cross-tenant erisim engellendi');
        }
        return $row;
    }
}
