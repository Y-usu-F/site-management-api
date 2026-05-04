<?php

namespace App\Services\Operation;

use App\Core\BaseService;
use App\Exceptions\NotFoundApiException;
use App\Exceptions\TenantAccessDeniedException;
use App\Libraries\ListQuery;
use App\Models\CommonAreaModel;
use Config\Database;

class CommonAreaService extends BaseService
{
    public function __construct(private readonly CommonAreaModel $model = new CommonAreaModel())
    {
        parent::__construct();
    }
    public function list(array $query): array { $q=ListQuery::normalize($query,['sortable'=>['id','name','status','created_at'],'filterable'=>['site_id','status','is_paid','requires_approval']]); $b=$this->model->builder()->select('*')->where('deleted_at',null); foreach($q['filters'] as $f=>$v){$b->where($f,$v);} if($q['search']!==''){$b->groupStart()->like('name',$q['search'])->orLike('code',$q['search'])->groupEnd();} $t=(int)$b->countAllResults(false); $i=$b->orderBy($q['sort'],$q['direction'])->limit($q['per_page'],($q['page']-1)*$q['per_page'])->get()->getResultArray(); return ListQuery::envelope($q['page'],$q['per_page'],$t,$i);}
    public function show(int $id): array { $this->assertAccessible($id); $r=$this->model->tenantFind($id); if(!is_array($r)){throw new NotFoundApiException('Common area bulunamadi');} return $r; }
    public function create(array $payload): array
    {
        $this->assertSiteAccessible((int) $payload['site_id']);
        $requiresApproval = array_key_exists('requires_approval', $payload)
            ? $this->toBoolInt($payload['requires_approval'])
            : 1;

        $this->model->insert([
            'site_id' => (int) $payload['site_id'],
            'name' => trim((string) $payload['name']),
            'code' => isset($payload['code']) ? trim((string) $payload['code']) : null,
            'description' => isset($payload['description']) ? trim((string) $payload['description']) : null,
            'capacity' => $payload['capacity'] ?? null,
            'requires_approval' => $requiresApproval,
            'is_paid' => array_key_exists('is_paid', $payload) ? $this->toBoolInt($payload['is_paid']) : 0,
            'fee_amount' => $payload['fee_amount'] ?? null,
            'currency' => (string) ($payload['currency'] ?? 'TRY'),
            'status' => (string) ($payload['status'] ?? 'active'),
        ], true);

        $id = (int) $this->model->getInsertID();
        $current = $this->show($id);
        $this->audit('operation.common_area.create.success', [
            'entity_type' => 'common_area',
            'entity_id' => $id,
            'new_values' => $current,
        ]);

        return $current;
    }
    public function update(int $id, array $payload): array
    {
        $old = $this->show($id);
        if (array_key_exists('site_id', $payload)) {
            $this->assertSiteAccessible((int) $payload['site_id']);
        }

        $data = [];
        foreach (['site_id', 'capacity', 'fee_amount'] as $field) {
            if (array_key_exists($field, $payload)) {
                $data[$field] = $payload[$field];
            }
        }
        foreach (['name', 'code', 'description', 'currency', 'status'] as $field) {
            if (array_key_exists($field, $payload)) {
                $data[$field] = $payload[$field] === null ? null : trim((string) $payload[$field]);
            }
        }
        foreach (['requires_approval', 'is_paid'] as $field) {
            if (array_key_exists($field, $payload)) {
                $data[$field] = $this->toBoolInt($payload[$field]);
            }
        }

        if ($data !== []) {
            $this->model->update($id, $data);
        }

        $new = $this->show($id);
        $this->audit('operation.common_area.update.success', [
            'entity_type' => 'common_area',
            'entity_id' => $id,
            'old_values' => $old,
            'new_values' => $new,
        ]);

        return $new;
    }
    public function delete(int $id): void { $old=$this->show($id); $this->model->delete($id); $this->audit('operation.common_area.delete.success',['entity_type'=>'common_area','entity_id'=>$id,'old_values'=>$old]); }
    private function assertSiteAccessible(int $siteId): void { $s=Database::connect()->table('sites')->where('id',$siteId)->where('deleted_at',null)->get(1)->getRowArray(); if(!is_array($s)){throw new NotFoundApiException('Site bulunamadi');} $ctx=(int)(service('request')->company_id??0); if($ctx>0 && (int)$s['company_id']!==$ctx){throw new TenantAccessDeniedException('Cross-tenant erisim engellendi');}}
    private function assertAccessible(int $id): void { $r=Database::connect()->table('common_areas')->where('id',$id)->get()->getRowArray(); if(!is_array($r)||($r['deleted_at']??null)!==null){throw new NotFoundApiException('Common area bulunamadi');} $ctx=(int)(service('request')->company_id??0); if($ctx>0 && (int)$r['company_id']!==$ctx){throw new TenantAccessDeniedException('Cross-tenant erisim engellendi');}}

    private function toBoolInt(mixed $value): int
    {
        if (is_bool($value)) {
            return $value ? 1 : 0;
        }
        if (is_int($value)) {
            return $value === 1 ? 1 : 0;
        }
        $normalized = strtolower(trim((string) $value));
        return in_array($normalized, ['1', 'true', 'yes', 'on'], true) ? 1 : 0;
    }
}
