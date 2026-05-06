<?php

namespace App\Services\Communication;

use App\Core\BaseService;
use App\Exceptions\ConflictApiException;
use App\Exceptions\NotFoundApiException;
use App\Exceptions\TenantAccessDeniedException;
use App\Libraries\ListQuery;
use App\Models\NotificationMessageModel;
use App\Models\NotificationRecipientModel;
use App\Support\RequestRuntime;
use Config\Database;

class NotificationRecipientService extends BaseService
{
    public function __construct(
        private readonly NotificationRecipientModel $model = new NotificationRecipientModel(),
        private readonly NotificationMessageModel $messageModel = new NotificationMessageModel(),
    ) {
        parent::__construct();
    }

    public function list(array $query): array
    {
        $q = ListQuery::normalize($query, ['sortable' => ['id', 'status', 'created_at'], 'filterable' => ['message_id', 'status']]);
        $b = $this->model->builder()->select('*')->where('deleted_at', null);
        foreach ($q['filters'] as $f => $v) {
            $b->where($f, $v);
        }
        $t = (int) $b->countAllResults(false);
        $i = $b->orderBy($q['sort'], $q['direction'])->limit($q['per_page'], ($q['page'] - 1) * $q['per_page'])->get()->getResultArray();
        return ListQuery::envelope($q['page'], $q['per_page'], $t, $i);
    }

    public function show(int $id): array
    {
        $this->assertAccessible($id);
        $r = $this->model->tenantFind($id);
        if (! is_array($r) || ($r['deleted_at'] ?? null) !== null) {
            throw new NotFoundApiException('Notification recipient bulunamadi');
        }
        return $r;
    }

    /**
     * @return array{unread_count:int}
     */
    public function unreadCount(): array
    {
        $companyId = $this->resolveCompanyId();
        if ($companyId <= 0) {
            return ['unread_count' => 0];
        }

        $userId = $this->resolveUserId();
        $residentProfileId = $this->resolveResidentProfileId();
        if ($userId <= 0 && $residentProfileId <= 0) {
            return ['unread_count' => 0];
        }

        $builder = Database::connect()
            ->table('notification_recipients nr')
            ->selectCount('nr.id', 'unread_count')
            ->join('notification_messages nm', 'nm.id = nr.message_id', 'inner')
            ->where('nr.company_id', $companyId)
            ->where('nr.deleted_at', null)
            ->where('nr.read_at', null)
            ->where('nm.company_id', $companyId)
            ->where('nm.deleted_at', null)
            ->where('nm.channel', 'in_app');

        $builder->groupStart();
        if ($userId > 0) {
            $builder->orWhere('nr.user_id', $userId);
        }
        if ($residentProfileId > 0) {
            $builder->orWhere('nr.resident_profile_id', $residentProfileId);
        }
        $builder->groupEnd();

        $row = $builder->get()->getRowArray();
        return ['unread_count' => (int) ($row['unread_count'] ?? 0)];
    }

    public function markRead(int $id): array
    {
        $old = $this->show($id);
        $msg = $this->messageModel->tenantFind((int) $old['message_id']);
        if (! is_array($msg) || (string) ($msg['channel'] ?? '') !== 'in_app') {
            throw new ConflictApiException('mark-read sadece in_app icin gecerli');
        }
        $this->model->update($id, ['read_at' => date('Y-m-d H:i:s')]);
        $n = $this->show($id);
        $this->audit('communication.notification_recipient.mark_read.success', ['entity_type' => 'notification_recipient', 'entity_id' => $id, 'old_values' => $old, 'new_values' => $n]);
        return $n;
    }

    private function assertAccessible(int $id): void
    {
        $row = Database::connect()->table('notification_recipients')->where('id', $id)->get()->getRowArray();
        if (! is_array($row) || ($row['deleted_at'] ?? null) !== null) {
            throw new NotFoundApiException('Notification recipient bulunamadi');
        }
        $ctx = (int) (service('request')->company_id ?? 0);
        if ($ctx > 0 && (int) $row['company_id'] !== $ctx) {
            throw new TenantAccessDeniedException('Cross-tenant erisim engellendi');
        }
    }

    private function resolveCompanyId(): int
    {
        $companyId = RequestRuntime::getCompanyId();
        if ($companyId > 0) {
            return $companyId;
        }

        return (int) (service('request')->company_id ?? 0);
    }

    private function resolveUserId(): int
    {
        $userId = RequestRuntime::getUserId();
        if ($userId > 0) {
            return $userId;
        }

        return (int) (service('request')->user?->id ?? 0);
    }

    private function resolveResidentProfileId(): int
    {
        $request = service('request');
        return (int) ($request->resident_profile_id ?? 0);
    }
}
