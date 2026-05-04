<?php

namespace App\Services\Communication;

use App\Core\BaseService;
use App\Exceptions\ConflictApiException;
use App\Exceptions\NotFoundApiException;
use App\Exceptions\TenantAccessDeniedException;
use App\Libraries\ListQuery;
use App\Models\NotificationTemplateModel;
use Config\Database;
use Throwable;

class NotificationTemplateService extends BaseService
{
    public function __construct(private readonly NotificationTemplateModel $model = new NotificationTemplateModel()) { parent::__construct(); }
    public function list(array $query): array { $q = ListQuery::normalize($query, ['sortable' => ['id','code','channel','locale','created_at'], 'filterable' => ['channel','locale','status']]); $b = $this->model->builder()->select('*')->where('deleted_at', null); foreach ($q['filters'] as $f=>$v) { $b->where($f,$v);} if ($q['search']!==''){ $b->groupStart()->like('code',$q['search'])->orLike('subject',$q['search'])->groupEnd(); } $t=(int)$b->countAllResults(false); $i=$b->orderBy($q['sort'],$q['direction'])->limit($q['per_page'],($q['page']-1)*$q['per_page'])->get()->getResultArray(); return ListQuery::envelope($q['page'],$q['per_page'],$t,$i);}
    public function show(int $id): array { $this->assertAccessible($id); $r=$this->model->tenantFind($id); if(!is_array($r)||($r['deleted_at']??null)!==null){throw new NotFoundApiException('Notification template bulunamadi');} return $r; }
    public function create(array $payload): array
    {
        $data = [
            'code' => trim((string) $payload['code']),
            'channel' => (string) $payload['channel'],
            'locale' => (string) ($payload['locale'] ?? 'tr'),
            'subject' => isset($payload['subject']) ? trim((string) $payload['subject']) : null,
            'body' => (string) $payload['body'],
            'status' => (string) ($payload['status'] ?? 'active'),
        ];
        $this->assertUniqueTemplate($data['code'], $data['channel'], $data['locale']);
        try {
            $this->model->insert($data, true);
        } catch (Throwable $e) {
            if ($this->isDuplicateKey($e)) {
                throw new ConflictApiException('code+channel+locale kombinasyonu zaten mevcut');
            }
            throw $e;
        }
        $id = (int) $this->model->getInsertID();
        $created = $this->show($id);
        $this->audit('communication.notification_template.create.success', ['entity_type' => 'notification_template', 'entity_id' => $id, 'new_values' => $created]);
        return $created;
    }

    public function update(int $id, array $payload): array
    {
        $old = $this->show($id);
        $data = [];
        foreach (['code', 'channel', 'locale', 'subject', 'body', 'status'] as $f) {
            if (array_key_exists($f, $payload)) {
                $data[$f] = is_string($payload[$f]) ? trim($payload[$f]) : $payload[$f];
            }
        }
        $nextCode = (string) ($data['code'] ?? $old['code']);
        $nextChannel = (string) ($data['channel'] ?? $old['channel']);
        $nextLocale = (string) ($data['locale'] ?? $old['locale']);
        $this->assertUniqueTemplate($nextCode, $nextChannel, $nextLocale, $id);
        if ($data !== []) {
            $this->model->update($id, $data);
        }
        $n = $this->show($id);
        $this->audit('communication.notification_template.update.success', ['entity_type' => 'notification_template', 'entity_id' => $id, 'old_values' => $old, 'new_values' => $n]);
        return $n;
    }
    public function delete(int $id): void { $old=$this->show($id); $this->model->delete($id); $this->audit('communication.notification_template.delete.success',['entity_type'=>'notification_template','entity_id'=>$id,'old_values'=>$old]); }

    private function assertUniqueTemplate(string $code, string $channel, string $locale, ?int $excludeId = null): void
    {
        $builder = $this->model->builder()
            ->where('code', $code)
            ->where('channel', $channel)
            ->where('locale', $locale)
            ->where('deleted_at', null);
        if ($excludeId !== null) {
            $builder->where('id !=', $excludeId);
        }
        if ($builder->get(1)->getRowArray() !== null) {
            throw new ConflictApiException('code+channel+locale kombinasyonu zaten mevcut');
        }
    }

    private function isDuplicateKey(Throwable $e): bool
    {
        $m = strtolower($e->getMessage());
        return str_contains($m, 'duplicate') || str_contains($m, 'unique');
    }

    private function assertAccessible(int $id): void
    {
        $row = Database::connect()->table('notification_templates')->where('id', $id)->get()->getRowArray();
        if (! is_array($row) || ($row['deleted_at'] ?? null) !== null) {
            throw new NotFoundApiException('Notification template bulunamadi');
        }
        $ctx = (int) (service('request')->company_id ?? 0);
        if ($ctx > 0 && (int) $row['company_id'] !== $ctx) {
            throw new TenantAccessDeniedException('Cross-tenant erisim engellendi');
        }
    }
}
