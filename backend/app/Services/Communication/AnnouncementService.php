<?php

namespace App\Services\Communication;

use App\Core\BaseService;
use App\Exceptions\ConflictApiException;
use App\Exceptions\NotFoundApiException;
use App\Exceptions\TenantAccessDeniedException;
use App\Libraries\ListQuery;
use App\Models\AnnouncementModel;
use App\Models\AnnouncementReadModel;
use App\Models\AnnouncementTargetModel;
use Config\Database;

class AnnouncementService extends BaseService
{
    public function __construct(
        private readonly AnnouncementModel $announcementModel = new AnnouncementModel(),
        private readonly AnnouncementTargetModel $targetModel = new AnnouncementTargetModel(),
        private readonly AnnouncementReadModel $readModel = new AnnouncementReadModel(),
    ) {
        parent::__construct();
    }

    public function list(array $query): array
    {
        $q = ListQuery::normalize($query, [
            'sortable' => ['id', 'status', 'publish_at', 'expires_at', 'created_at'],
            'filterable' => ['status'],
        ]);
        $builder = $this->announcementModel->builder()->select('*')->where('deleted_at', null);
        foreach ($q['filters'] as $f => $v) {
            $builder->where($f, $v);
        }
        if ($q['search'] !== '') {
            $builder->groupStart()->like('title', $q['search'])->orLike('body', $q['search'])->groupEnd();
        }
        $total = (int) $builder->countAllResults(false);
        $items = $builder->orderBy($q['sort'], $q['direction'])->limit($q['per_page'], ($q['page'] - 1) * $q['per_page'])->get()->getResultArray();
        return ListQuery::envelope($q['page'], $q['per_page'], $total, $items);
    }

    public function show(int $id): array
    {
        $this->assertAnnouncementAccessible($id);
        $row = $this->announcementModel->tenantFind($id);
        if (! is_array($row)) {
            throw new NotFoundApiException('Announcement bulunamadi');
        }
        return $row;
    }

    public function create(array $payload): array
    {
        $this->assertPublishExpireConsistency($payload['publish_at'] ?? null, $payload['expires_at'] ?? null);
        $this->announcementModel->insert([
            'title' => trim((string) $payload['title']),
            'body' => trim((string) $payload['body']),
            'status' => 'draft',
            'publish_at' => $payload['publish_at'] ?? null,
            'expires_at' => $payload['expires_at'] ?? null,
            'published_at' => null,
        ], true);
        $id = (int) $this->announcementModel->getInsertID();

        if (isset($payload['targets']) && is_array($payload['targets'])) {
            $this->replaceTargets($id, $payload['targets']);
        }

        $created = $this->show($id);
        $this->audit('communication.announcement.create.success', ['entity_type' => 'announcement', 'entity_id' => $id, 'new_values' => $created]);
        return $created;
    }

    public function update(int $id, array $payload): array
    {
        $old = $this->show($id);
        if (in_array((string) $old['status'], ['archived', 'cancelled'], true)) {
            throw new ConflictApiException('archived/cancelled announcement guncellenemez');
        }
        if ((string) $old['status'] === 'published' && (array_key_exists('title', $payload) || array_key_exists('body', $payload))) {
            throw new ConflictApiException('published announcement title/body guncellenemez');
        }
        $nextPublish = $payload['publish_at'] ?? $old['publish_at'];
        $nextExpire = $payload['expires_at'] ?? $old['expires_at'];
        $this->assertPublishExpireConsistency($nextPublish, $nextExpire);

        $data = [];
        foreach (['title', 'body', 'publish_at', 'expires_at'] as $f) {
            if (array_key_exists($f, $payload)) {
                $data[$f] = is_string($payload[$f]) ? trim($payload[$f]) : $payload[$f];
            }
        }
        if ($data !== []) {
            $this->announcementModel->update($id, $data);
        }
        if (isset($payload['targets']) && is_array($payload['targets'])) {
            $this->replaceTargets($id, $payload['targets']);
        }

        $new = $this->show($id);
        $this->audit('communication.announcement.update.success', ['entity_type' => 'announcement', 'entity_id' => $id, 'old_values' => $old, 'new_values' => $new]);
        return $new;
    }

    public function delete(int $id): void
    {
        $old = $this->show($id);
        $this->announcementModel->delete($id);
        $this->audit('communication.announcement.delete.success', ['entity_type' => 'announcement', 'entity_id' => $id, 'old_values' => $old]);
    }

    public function publish(int $id): array
    {
        $old = $this->show($id);
        if ((string) $old['status'] !== 'draft') {
            throw new ConflictApiException('publish sadece draft durumunda yapilabilir');
        }
        $this->announcementModel->update($id, ['status' => 'published', 'published_at' => date('Y-m-d H:i:s')]);
        $new = $this->show($id);
        $this->audit('communication.announcement.publish.success', ['entity_type' => 'announcement', 'entity_id' => $id, 'old_values' => $old, 'new_values' => $new]);
        return $new;
    }

    public function archive(int $id): array
    {
        $old = $this->show($id);
        if ((string) $old['status'] !== 'published') {
            throw new ConflictApiException('archive sadece published durumunda yapilabilir');
        }
        $this->announcementModel->update($id, ['status' => 'archived']);
        $new = $this->show($id);
        $this->audit('communication.announcement.archive.success', ['entity_type' => 'announcement', 'entity_id' => $id, 'old_values' => $old, 'new_values' => $new]);
        return $new;
    }

    public function cancel(int $id): array
    {
        $old = $this->show($id);
        if (! in_array((string) $old['status'], ['draft', 'published'], true)) {
            throw new ConflictApiException('cancel sadece draft/published durumunda yapilabilir');
        }
        $this->announcementModel->update($id, ['status' => 'cancelled']);
        $new = $this->show($id);
        $this->audit('communication.announcement.cancel.success', ['entity_type' => 'announcement', 'entity_id' => $id, 'old_values' => $old, 'new_values' => $new]);
        return $new;
    }

    public function markRead(int $id): array
    {
        $announcement = $this->show($id);
        if ((string) $announcement['status'] !== 'published') {
            throw new ConflictApiException('mark-read sadece published duyuru icin gecerli');
        }
        $expiresAt = $announcement['expires_at'] ?? null;
        if ($expiresAt !== null && strtotime((string) $expiresAt) < time()) {
            throw new ConflictApiException('expire olmus duyuru mark-read olamaz');
        }

        $userId = service('request')->user?->id ?? null;
        $residentId = isset(service('request')->resident_profile_id) ? (int) service('request')->resident_profile_id : null;
        if ($userId === null && $residentId === null) {
            throw new ConflictApiException('mark-read icin actor bilgisi gerekli');
        }
        $builder = $this->readModel->builder()
            ->where('announcement_id', $id)
            ->where('deleted_at', null);
        if ($userId !== null) {
            $builder->where('user_id', $userId);
        } else {
            $builder->where('resident_profile_id', $residentId);
        }
        $existing = $builder->get(1)->getRowArray();

        if ($existing !== null) {
            $read = $this->readModel->tenantFind((int) $existing['id']);
            return is_array($read) ? $read : $existing;
        }

        $this->readModel->insert([
            'announcement_id' => $id,
            'user_id' => $userId,
            'resident_profile_id' => $residentId,
            'read_at' => date('Y-m-d H:i:s'),
        ], true);
        $readId = (int) $this->readModel->getInsertID();
        $read = $this->readModel->tenantFind($readId);
        $this->audit('communication.announcement.mark_read.success', ['entity_type' => 'announcement_read', 'entity_id' => $readId, 'new_values' => $read]);
        return is_array($read) ? $read : [];
    }

    public function listReads(int $announcementId, array $query): array
    {
        $this->show($announcementId);
        $q = ListQuery::normalize($query, ['sortable' => ['id', 'read_at', 'created_at'], 'filterable' => ['user_id', 'resident_profile_id']]);
        $builder = $this->readModel->builder()->select('*')->where('announcement_id', $announcementId)->where('deleted_at', null);
        foreach ($q['filters'] as $f => $v) {
            $builder->where($f, $v);
        }
        $total = (int) $builder->countAllResults(false);
        $items = $builder->orderBy($q['sort'], $q['direction'])->limit($q['per_page'], ($q['page'] - 1) * $q['per_page'])->get()->getResultArray();
        return ListQuery::envelope($q['page'], $q['per_page'], $total, $items);
    }

    public function listTargets(int $announcementId, array $query): array
    {
        $this->show($announcementId);
        $q = ListQuery::normalize($query, ['sortable' => ['id', 'target_type', 'created_at'], 'filterable' => ['target_type']]);
        $builder = $this->targetModel->builder()->select('*')->where('announcement_id', $announcementId)->where('deleted_at', null);
        foreach ($q['filters'] as $f => $v) {
            $builder->where($f, $v);
        }
        $total = (int) $builder->countAllResults(false);
        $items = $builder->orderBy($q['sort'], $q['direction'])->limit($q['per_page'], ($q['page'] - 1) * $q['per_page'])->get()->getResultArray();
        return ListQuery::envelope($q['page'], $q['per_page'], $total, $items);
    }

    /**
     * @param list<array<string,mixed>> $targets
     */
    private function replaceTargets(int $announcementId, array $targets): void
    {
        $this->targetModel->builder()->where('announcement_id', $announcementId)->where('deleted_at', null)->set(['deleted_at' => date('Y-m-d H:i:s')])->update();
        foreach ($targets as $target) {
            $type = (string) ($target['target_type'] ?? '');
            $targetId = isset($target['target_id']) ? (string) $target['target_id'] : null;
            $this->assertTargetConsistency($type, $targetId);
            $this->targetModel->insert(['announcement_id' => $announcementId, 'target_type' => $type, 'target_id' => $targetId], true);
        }
    }

    private function assertTargetConsistency(string $targetType, ?string $targetId): void
    {
        $allowed = ['site', 'block', 'unit', 'role', 'resident', 'all'];
        if (! in_array($targetType, $allowed, true)) {
            throw new ConflictApiException('Gecersiz target_type');
        }
        if ($targetType === 'all') {
            if ($targetId !== null && $targetId !== '') {
                throw new ConflictApiException('target_type all icin target_id null olmalidir');
            }
            return;
        }
        if ($targetId === null || $targetId === '') {
            throw new ConflictApiException('target_id zorunlu');
        }
        $ctxCompanyId = (int) (service('request')->company_id ?? 0);
        $db = Database::connect();
        if (in_array($targetType, ['site', 'block', 'unit', 'resident'], true)) {
            $table = $targetType === 'resident' ? 'resident_profiles' : ($targetType . 's');
            $row = $db->table($table)->where('id', (int) $targetId)->where('deleted_at', null)->get(1)->getRowArray();
            if (! is_array($row)) {
                throw new NotFoundApiException('Target kaydi bulunamadi');
            }
            if ($ctxCompanyId > 0 && (int) $row['company_id'] !== $ctxCompanyId) {
                throw new TenantAccessDeniedException('Cross-tenant erisim engellendi');
            }
            return;
        }
        if ($targetType === 'role') {
            if (ctype_digit($targetId)) {
                $row = $db->table('roles')->where('id', (int) $targetId)->where('deleted_at', null)->get(1)->getRowArray();
            } else {
                $row = $db->table('roles')->where('code', $targetId)->where('deleted_at', null)->get(1)->getRowArray();
            }
            if (! is_array($row)) {
                throw new NotFoundApiException('Role target bulunamadi');
            }
            $roleCompanyId = isset($row['company_id']) && $row['company_id'] !== null ? (int) $row['company_id'] : null;
            if ($ctxCompanyId > 0 && $roleCompanyId !== null && $roleCompanyId !== $ctxCompanyId) {
                throw new TenantAccessDeniedException('Cross-tenant erisim engellendi');
            }
        }
    }

    private function assertPublishExpireConsistency(mixed $publishAt, mixed $expiresAt): void
    {
        if ($publishAt === null || $expiresAt === null || $publishAt === '' || $expiresAt === '') {
            return;
        }
        if (strtotime((string) $expiresAt) < strtotime((string) $publishAt)) {
            throw new ConflictApiException('expires_at publish_at tarihinden kucuk olamaz');
        }
    }

    private function assertAnnouncementAccessible(int $id): void
    {
        $row = Database::connect()->table('announcements')->where('id', $id)->get()->getRowArray();
        if (! is_array($row)) {
            throw new NotFoundApiException('Announcement bulunamadi');
        }
        $ctx = (int) (service('request')->company_id ?? 0);
        if ($ctx > 0 && (int) $row['company_id'] !== $ctx) {
            throw new TenantAccessDeniedException('Cross-tenant erisim engellendi');
        }
        if (($row['deleted_at'] ?? null) !== null) {
            throw new NotFoundApiException('Announcement bulunamadi');
        }
    }
}
