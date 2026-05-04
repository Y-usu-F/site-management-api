<?php

namespace App\Services\Communication;

use App\Core\BaseService;
use App\Exceptions\ConflictApiException;
use App\Exceptions\NotFoundApiException;
use App\Exceptions\TenantAccessDeniedException;
use App\Libraries\ListQuery;
use App\Models\NotificationDeliveryLogModel;
use App\Models\NotificationMessageModel;
use App\Models\NotificationRecipientModel;
use App\Models\NotificationTemplateModel;
use Config\Database;

class NotificationMessageService extends BaseService
{
    public function __construct(
        private readonly NotificationMessageModel $model = new NotificationMessageModel(),
        private readonly NotificationTemplateModel $templateModel = new NotificationTemplateModel(),
        private readonly NotificationRecipientModel $recipientModel = new NotificationRecipientModel(),
        private readonly NotificationDeliveryLogModel $logModel = new NotificationDeliveryLogModel(),
    ) { parent::__construct(); }

    public function list(array $query): array { $q=ListQuery::normalize($query,['sortable'=>['id','channel','status','scheduled_at','created_at'],'filterable'=>['channel','status']]); $b=$this->model->builder()->select('*')->where('deleted_at',null); foreach($q['filters'] as $f=>$v){$b->where($f,$v);} $t=(int)$b->countAllResults(false); $i=$b->orderBy($q['sort'],$q['direction'])->limit($q['per_page'],($q['page']-1)*$q['per_page'])->get()->getResultArray(); return ListQuery::envelope($q['page'],$q['per_page'],$t,$i);}
    public function show(int $id): array { $this->assertAccessible($id); $r=$this->model->tenantFind($id); if(!is_array($r)||($r['deleted_at']??null)!==null){throw new NotFoundApiException('Notification message bulunamadi');} return $r; }

    public function create(array $payload): array
    {
        $template = null;
        if (isset($payload['template_id'])) {
            $template = $this->templateModel->tenantFind((int) $payload['template_id']);
            if (! is_array($template) || (string) ($template['status'] ?? '') !== 'active') {
                throw new NotFoundApiException('Active template bulunamadi');
            }
        }
        $channel = (string) ($payload['channel'] ?? ($template['channel'] ?? 'in_app'));
        $subject = (string) ($payload['subject'] ?? ($template['subject'] ?? ''));
        $bodyRaw = (string) ($payload['body'] ?? ($template['body'] ?? ''));
        $payloadData = isset($payload['payload_json']) && is_array($payload['payload_json']) ? $payload['payload_json'] : [];
        $body = $this->renderTemplateBody($bodyRaw, $payloadData);
        $this->model->insert(['template_id'=>isset($payload['template_id'])?(int)$payload['template_id']:null,'channel'=>$channel,'subject'=>$subject !== '' ? $subject : null,'body'=>$body,'payload_json'=>$payloadData === [] ? null : json_encode($payloadData, JSON_UNESCAPED_UNICODE),'status'=>'draft','scheduled_at'=>$payload['scheduled_at'] ?? null,'sent_at'=>null], true);
        $id=(int)$this->model->getInsertID();
        if (isset($payload['recipients']) && is_array($payload['recipients'])) {
            foreach ($payload['recipients'] as $recipient) {
                if (! is_array($recipient)) {
                    continue;
                }
                $this->createRecipient($id, $channel, $recipient);
            }
        }
        $c=$this->show($id); $this->audit('communication.notification_message.create.success',['entity_type'=>'notification_message','entity_id'=>$id,'new_values'=>$c]); return $c;
    }

    public function queue(int $id): array
    {
        $old=$this->show($id);
        if (! in_array((string)$old['status'], ['draft','failed'], true)) { throw new ConflictApiException('Sadece draft/failed queue edilebilir'); }
        $this->model->update($id, ['status'=>'queued']);
        $this->logModel->insert(['message_id'=>$id,'recipient_id'=>null,'provider'=>null,'channel'=>(string)$old['channel'],'status'=>'success','provider_reference'=>null,'error_message'=>null,'attempted_at'=>date('Y-m-d H:i:s')], true);
        $n=$this->show($id); $this->audit('communication.notification_message.queue.success',['entity_type'=>'notification_message','entity_id'=>$id,'old_values'=>$old,'new_values'=>$n]); return $n;
    }

    public function cancel(int $id): array
    {
        $old=$this->show($id);
        if (! in_array((string)$old['status'], ['draft','queued'], true)) { throw new ConflictApiException('Sadece draft/queued cancel edilebilir'); }
        $this->model->update($id, ['status'=>'cancelled']);
        $n=$this->show($id); $this->audit('communication.notification_message.cancel.success',['entity_type'=>'notification_message','entity_id'=>$id,'old_values'=>$old,'new_values'=>$n]); return $n;
    }

    private function createRecipient(int $messageId, string $channel, array $recipient): void
    {
        $data = [
            'message_id' => $messageId,
            'user_id' => isset($recipient['user_id']) ? (int) $recipient['user_id'] : null,
            'resident_profile_id' => isset($recipient['resident_profile_id']) ? (int) $recipient['resident_profile_id'] : null,
            'email' => isset($recipient['email']) ? trim((string) $recipient['email']) : null,
            'phone' => isset($recipient['phone']) ? trim((string) $recipient['phone']) : null,
            'status' => 'pending',
        ];
        if ($channel === 'email' && $data['email'] === null) { throw new ConflictApiException('email channel icin email zorunlu'); }
        if ($channel === 'sms' && $data['phone'] === null) { throw new ConflictApiException('sms channel icin phone zorunlu'); }
        if ($channel === 'in_app' && $data['user_id'] === null && $data['resident_profile_id'] === null) { throw new ConflictApiException('in_app icin user_id veya resident_profile_id zorunlu'); }
        $this->recipientModel->insert($data, true);
    }

    private function renderTemplateBody(string $body, array $payload): string
    {
        foreach ($payload as $k => $v) {
            if (is_scalar($v)) {
                $body = str_replace('{{' . $k . '}}', (string) $v, $body);
            }
        }
        return $body;
    }

    private function assertAccessible(int $id): void
    {
        $row = Database::connect()->table('notification_messages')->where('id', $id)->get()->getRowArray();
        if (! is_array($row) || ($row['deleted_at'] ?? null) !== null) {
            throw new NotFoundApiException('Notification message bulunamadi');
        }
        $ctx = (int) (service('request')->company_id ?? 0);
        if ($ctx > 0 && (int) $row['company_id'] !== $ctx) {
            throw new TenantAccessDeniedException('Cross-tenant erisim engellendi');
        }
    }
}
