<?php

namespace App\Services\Operation;

use App\Core\BaseService;
use App\Exceptions\NotFoundApiException;
use App\Libraries\ListQuery;
use App\Models\ServiceRequestFileModel;

class ServiceRequestFileService extends BaseService
{
    public function __construct(
        private readonly ServiceRequestFileModel $model = new ServiceRequestFileModel(),
        private readonly ServiceRequestService $requestService = new ServiceRequestService()
    ) {
        parent::__construct();
    }

    public function listByRequest(int $serviceRequestId, array $query): array
    {
        $this->requestService->show($serviceRequestId);
        $q = ListQuery::normalize($query, [
            'sortable' => ['id', 'file_name', 'created_at'],
            'filterable' => ['uploaded_by'],
        ]);
        $builder = $this->model->builder()
            ->select('*')
            ->where('service_request_id', $serviceRequestId)
            ->where('deleted_at', null);
        foreach ($q['filters'] as $f => $v) {
            $builder->where($f, $v);
        }
        if ($q['search'] !== '') {
            $builder->groupStart()->like('file_name', $q['search'])->orLike('file_path', $q['search'])->groupEnd();
        }
        $total = (int) $builder->countAllResults(false);
        $items = $builder->orderBy($q['sort'], $q['direction'])->limit($q['per_page'], ($q['page'] - 1) * $q['per_page'])->get()->getResultArray();
        return ListQuery::envelope($q['page'], $q['per_page'], $total, $items);
    }

    public function create(int $serviceRequestId, array $payload): array
    {
        $this->requestService->assertCommentable($serviceRequestId);
        $userId = service('request')->user?->id ?? null;
        $this->model->insert([
            'service_request_id' => $serviceRequestId,
            'file_name' => trim((string) $payload['file_name']),
            'file_path' => trim((string) $payload['file_path']),
            'mime_type' => isset($payload['mime_type']) ? trim((string) $payload['mime_type']) : null,
            'size_bytes' => isset($payload['size_bytes']) ? (int) $payload['size_bytes'] : null,
            'uploaded_by' => $userId,
        ], true);
        $id = (int) $this->model->getInsertID();
        $created = $this->show($id);
        $this->audit('operation.service_request_file.create.success', ['entity_type' => 'service_request_file', 'entity_id' => $id, 'new_values' => $created]);
        return $created;
    }

    public function delete(int $id): void
    {
        $current = $this->show($id);
        $this->model->delete($id);
        $this->audit('operation.service_request_file.delete.success', ['entity_type' => 'service_request_file', 'entity_id' => $id, 'old_values' => $current]);
    }

    private function show(int $id): array
    {
        $row = $this->model->tenantFind($id);
        if (! is_array($row) || ($row['deleted_at'] ?? null) !== null) {
            throw new NotFoundApiException('Service request file bulunamadi');
        }
        return $row;
    }
}
