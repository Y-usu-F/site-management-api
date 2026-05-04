<?php

namespace App\Services\Operation;

use App\Core\BaseService;
use App\Exceptions\ConflictApiException;
use App\Exceptions\NotFoundApiException;
use App\Libraries\ListQuery;
use App\Models\ServiceRequestCommentModel;
use Config\Database;

class ServiceRequestCommentService extends BaseService
{
    public function __construct(
        private readonly ServiceRequestCommentModel $model = new ServiceRequestCommentModel(),
        private readonly ServiceRequestService $requestService = new ServiceRequestService()
    ) {
        parent::__construct();
    }

    public function listByRequest(int $serviceRequestId, array $query): array
    {
        $this->requestService->show($serviceRequestId);
        $q = ListQuery::normalize($query, [
            'sortable' => ['id', 'created_at'],
            'filterable' => ['visibility', 'user_id'],
        ]);
        $builder = $this->model->builder()
            ->select('*')
            ->where('service_request_id', $serviceRequestId)
            ->where('deleted_at', null);
        foreach ($q['filters'] as $f => $v) {
            $builder->where($f, $v);
        }
        if (! $this->isInternalCommentAllowed()) {
            $builder->where('visibility', 'public');
        }
        $total = (int) $builder->countAllResults(false);
        $items = $builder->orderBy($q['sort'], $q['direction'])->limit($q['per_page'], ($q['page'] - 1) * $q['per_page'])->get()->getResultArray();
        return ListQuery::envelope($q['page'], $q['per_page'], $total, $items);
    }

    public function create(int $serviceRequestId, array $payload): array
    {
        $request = $this->requestService->assertCommentable($serviceRequestId);
        $visibility = (string) ($payload['visibility'] ?? 'public');
        if ($visibility === 'internal' && ! $this->isInternalCommentAllowed()) {
            throw new ConflictApiException('internal comment only management users');
        }
        $userId = service('request')->user?->id ?? null;
        $this->model->insert([
            'service_request_id' => $serviceRequestId,
            'user_id' => $userId,
            'comment' => trim((string) $payload['comment']),
            'visibility' => $visibility,
        ], true);
        $id = (int) $this->model->getInsertID();
        $row = $this->show($id);
        $this->audit('operation.service_request_comment.create.success', [
            'entity_type' => 'service_request_comment',
            'entity_id' => $id,
            'meta' => ['request_id' => (int) $request['id']],
            'new_values' => $row,
        ]);
        return $row;
    }

    public function show(int $id): array
    {
        $row = $this->model->tenantFind($id);
        if (! is_array($row) || ($row['deleted_at'] ?? null) !== null) {
            throw new NotFoundApiException('Service request comment bulunamadi');
        }
        return $row;
    }

    private function isInternalCommentAllowed(): bool
    {
        $roles = service('request')->roles ?? [];
        return in_array('super_admin', $roles, true)
            || in_array('company_admin', $roles, true)
            || in_array('employee', $roles, true);
    }
}
