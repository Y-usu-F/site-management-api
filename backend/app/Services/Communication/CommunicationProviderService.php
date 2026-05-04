<?php

namespace App\Services\Communication;

use App\Core\BaseService;
use App\Exceptions\NotFoundApiException;
use App\Exceptions\TenantAccessDeniedException;
use App\Libraries\ListQuery;
use App\Models\CommunicationProviderModel;
use Config\Database;

class CommunicationProviderService extends BaseService
{
    public function __construct(private readonly CommunicationProviderModel $model = new CommunicationProviderModel()) { parent::__construct(); }
    public function list(array $query): array { $q=ListQuery::normalize($query,['sortable'=>['id','channel','provider_name','created_at'],'filterable'=>['channel','status','is_default']]); $b=$this->model->builder()->select('*')->where('deleted_at',null); foreach($q['filters'] as $f=>$v){$b->where($f,$v);} $t=(int)$b->countAllResults(false); $i=$b->orderBy($q['sort'],$q['direction'])->limit($q['per_page'],($q['page']-1)*$q['per_page'])->get()->getResultArray(); return ListQuery::envelope($q['page'],$q['per_page'],$t,$i);}
    public function show(int $id): array { $this->assertAccessible($id); $r=$this->model->tenantFind($id); if(!is_array($r)||($r['deleted_at']??null)!==null){throw new NotFoundApiException('Communication provider bulunamadi');} return $r; }
    public function create(array $payload): array { $cfg=$payload['config_json'] ?? null; $this->model->insert(['channel'=>(string)$payload['channel'],'provider_name'=>trim((string)$payload['provider_name']),'config_json'=>is_array($cfg)?json_encode($cfg,JSON_UNESCAPED_UNICODE):$cfg,'is_default'=>!empty($payload['is_default'])?1:0,'status'=>(string)($payload['status']??'active')],true); $id=(int)$this->model->getInsertID(); if(!empty($payload['is_default'])){$this->setDefault($id);} $c=$this->show($id); $this->audit('communication.communication_provider.create.success',['entity_type'=>'communication_provider','entity_id'=>$id,'new_values'=>$this->maskProviderSensitive($c)]); return $c; }
    public function update(int $id,array $payload): array { $old=$this->show($id); $d=[]; foreach(['channel','provider_name','status'] as $f){if(array_key_exists($f,$payload)){$d[$f]=trim((string)$payload[$f]);}} if(array_key_exists('config_json',$payload)){$d['config_json']=is_array($payload['config_json'])?json_encode($payload['config_json'],JSON_UNESCAPED_UNICODE):$payload['config_json'];} if(array_key_exists('is_default',$payload)){$d['is_default']=!empty($payload['is_default'])?1:0;} if($d!==[]){$this->model->update($id,$d);} if(!empty($payload['is_default'])){$this->setDefault($id);} $n=$this->show($id); $this->audit('communication.communication_provider.update.success',['entity_type'=>'communication_provider','entity_id'=>$id,'old_values'=>$this->maskProviderSensitive($old),'new_values'=>$this->maskProviderSensitive($n)]); return $n; }
    public function delete(int $id): void { $old=$this->show($id); $this->model->delete($id); $this->audit('communication.communication_provider.delete.success',['entity_type'=>'communication_provider','entity_id'=>$id,'old_values'=>$this->maskProviderSensitive($old)]); }
    public function setDefault(int $id): array
    {
        $row = $this->show($id);
        $channel = (string) $row['channel'];
        $db = Database::connect();
        $db->transStart();
        $this->model->builder()
            ->where('channel', $channel)
            ->where('deleted_at', null)
            ->set(['is_default' => 0])
            ->update();
        $this->model->update($id, ['is_default' => 1, 'status' => 'active']);
        $db->transComplete();
        $n = $this->show($id);
        $this->audit('communication.communication_provider.set_default.success', ['entity_type' => 'communication_provider', 'entity_id' => $id, 'new_values' => $this->maskProviderSensitive($n)]);
        return $n;
    }
    private function maskProviderSensitive(array $data): array { if (!isset($data['config_json']) || $data['config_json']===null) { return $data; } $cfg = is_string($data['config_json']) ? json_decode($data['config_json'], true) : $data['config_json']; if (is_array($cfg)) { foreach (['password','api_key','secret','token'] as $k) { if (array_key_exists($k,$cfg)) { $cfg[$k] = '***'; } } $data['config_json'] = $cfg; } return $data; }
    private function assertAccessible(int $id): void { $row=Database::connect()->table('communication_providers')->where('id',$id)->get()->getRowArray(); if(!is_array($row)||($row['deleted_at']??null)!==null){throw new NotFoundApiException('Communication provider bulunamadi');} $ctx=(int)(service('request')->company_id??0); if($ctx>0 && (int)$row['company_id']!==$ctx){throw new TenantAccessDeniedException('Cross-tenant erisim engellendi');} }
}
