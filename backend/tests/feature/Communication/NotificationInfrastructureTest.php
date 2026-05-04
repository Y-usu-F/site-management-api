<?php

namespace Tests\Feature\Communication;

use Tests\Support\FeatureDatabaseTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Database;

final class NotificationInfrastructureTest extends FeatureDatabaseTestCase
{
    use FeatureTestTrait;
    use DatabaseTestTrait;



    public function testTemplateUniqueCodeChannelLocaleEngellenir(): void
    {
        [$token] = $this->createTenantAdmin('n1@example.com');
        $payload = [
            'code' => 'DUE_REMINDER',
            'channel' => 'email',
            'locale' => 'tr',
            'subject' => 'Konu',
            'body' => 'Merhaba {{name}}',
        ];
        $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/notification-templates/', $payload)->assertStatus(200);
        $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/notification-templates/', $payload)->assertStatus(409);
    }

    public function testMessageTemplateIleRenderEdilir(): void
    {
        [$token] = $this->createTenantAdmin('n2@example.com');
        $tpl = $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/notification-templates/', [
            'code' => 'PAYMENT_INFO',
            'channel' => 'email',
            'locale' => 'tr',
            'subject' => 'Bilgi',
            'body' => 'Merhaba {{name}}, borc {{amount}}',
        ]);
        $tplId = (int) json_decode($tpl->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data']['id'];

        $msg = $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/notification-messages/', [
            'template_id' => $tplId,
            'channel' => 'email',
            'payload_json' => ['name' => 'Ali', 'amount' => '1200'],
            'recipients' => [['email' => 'ali@example.com']],
        ]);
        $msg->assertStatus(200);
        $body = json_decode($msg->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data']['body'];
        $this->assertSame('Merhaba Ali, borc 1200', $body);
    }

    public function testQueueCancelStatusTransitionKurallari(): void
    {
        [$token] = $this->createTenantAdmin('n3@example.com');
        $msg = $this->createMessage($token, ['channel' => 'email', 'body' => 'Test', 'recipients' => [['email' => 'x@example.com']]]);
        $id = (int) $msg['id'];
        $this->withHeaders(['Authorization' => 'Bearer ' . $token])->post('/api/v1/notification-messages/' . $id . '/queue')->assertStatus(200);
        $this->withHeaders(['Authorization' => 'Bearer ' . $token])->post('/api/v1/notification-messages/' . $id . '/queue')->assertStatus(409);
        $this->withHeaders(['Authorization' => 'Bearer ' . $token])->post('/api/v1/notification-messages/' . $id . '/cancel')->assertStatus(200);
    }

    public function testSentMessageCancelEdilemez(): void
    {
        [$token] = $this->createTenantAdmin('n4@example.com');
        $msg = $this->createMessage($token, ['channel' => 'email', 'body' => 'Sent', 'recipients' => [['email' => 'y@example.com']]]);
        $id = (int) $msg['id'];
        Database::connect()->table('notification_messages')->where('id', $id)->update(['status' => 'sent']);
        $this->withHeaders(['Authorization' => 'Bearer ' . $token])->post('/api/v1/notification-messages/' . $id . '/cancel')->assertStatus(409);
    }

    public function testEmailRecipientEmailZorunlu(): void
    {
        [$token] = $this->createTenantAdmin('n5@example.com');
        $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/notification-messages/', [
            'channel' => 'email',
            'body' => 'mail',
            'recipients' => [['phone' => '555']],
        ])->assertStatus(409);
    }

    public function testSmsRecipientPhoneZorunlu(): void
    {
        [$token] = $this->createTenantAdmin('n6@example.com');
        $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/notification-messages/', [
            'channel' => 'sms',
            'body' => 'sms',
            'recipients' => [['email' => 'mail@example.com']],
        ])->assertStatus(409);
    }

    public function testInAppRecipientUserVeyaResidentZorunlu(): void
    {
        [$token] = $this->createTenantAdmin('n7@example.com');
        $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/notification-messages/', [
            'channel' => 'in_app',
            'body' => 'inapp',
            'recipients' => [['email' => 'x@example.com']],
        ])->assertStatus(409);
    }

    public function testMarkReadSadeceInAppRecipientIcinCalisir(): void
    {
        [$token, $userId] = $this->createTenantAdmin('n8@example.com');
        $inApp = $this->createMessage($token, ['channel' => 'in_app', 'body' => 'inapp', 'recipients' => [['user_id' => $userId]]]);
        $rid = (int) Database::connect()->table('notification_recipients')->where('message_id', (int) $inApp['id'])->get()->getRowArray()['id'];
        $this->withHeaders(['Authorization' => 'Bearer ' . $token])->post('/api/v1/notification-recipients/' . $rid . '/mark-read')->assertStatus(200);

        $email = $this->createMessage($token, ['channel' => 'email', 'body' => 'mail', 'recipients' => [['email' => 'e@example.com']]]);
        $rid2 = (int) Database::connect()->table('notification_recipients')->where('message_id', (int) $email['id'])->get()->getRowArray()['id'];
        $this->withHeaders(['Authorization' => 'Bearer ' . $token])->post('/api/v1/notification-recipients/' . $rid2 . '/mark-read')->assertStatus(409);
    }

    public function testProviderDefaultTekilKalir(): void
    {
        [$token] = $this->createTenantAdmin('n9@example.com');
        $p1 = $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/communication-providers/', [
            'channel' => 'email',
            'provider_name' => 'smtp-a',
            'is_default' => true,
            'status' => 'active',
        ]);
        $p1Id = (int) json_decode($p1->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data']['id'];
        $p2 = $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/communication-providers/', [
            'channel' => 'email',
            'provider_name' => 'smtp-b',
            'is_default' => true,
            'status' => 'active',
        ]);
        $p2Id = (int) json_decode($p2->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data']['id'];

        $db = Database::connect();
        $one = $db->table('communication_providers')->where('id', $p1Id)->get()->getRowArray();
        $two = $db->table('communication_providers')->where('id', $p2Id)->get()->getRowArray();
        $this->assertSame(0, (int) $one['is_default']);
        $this->assertSame(1, (int) $two['is_default']);
    }

    public function testProviderConfigAuditMasking(): void
    {
        [$token] = $this->createTenantAdmin('n10@example.com');
        $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/communication-providers/', [
            'channel' => 'email',
            'provider_name' => 'smtp-mask',
            'config_json' => ['username' => 'u', 'password' => 'secret-pass', 'api_key' => 'abc'],
            'is_default' => true,
            'status' => 'active',
        ])->assertStatus(200);

        $row = Database::connect()->table('audit_logs')
            ->where('event', 'communication.communication_provider.create.success')
            ->orderBy('id', 'DESC')
            ->get(1)
            ->getRowArray();
        $this->assertIsArray($row);
        $new = json_decode((string) ($row['new_values'] ?? '{}'), true);
        $cfg = $new['config_json'] ?? [];
        $this->assertSame('***', $cfg['password'] ?? null);
        $this->assertSame('***', $cfg['api_key'] ?? null);
    }

    public function testCrossTenantTemplateMessageProviderErisimi403Doner(): void
    {
        [$tokenA] = $this->createTenantAdmin('na@example.com');
        [$tokenB] = $this->createTenantAdmin('nb@example.com');

        $tpl = $this->withHeaders(['Authorization' => 'Bearer ' . $tokenA])->withBodyFormat('json')->post('/api/v1/notification-templates/', [
            'code' => 'X',
            'channel' => 'email',
            'body' => 'b',
        ]);
        $tplId = (int) json_decode($tpl->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data']['id'];

        $msg = $this->createMessage($tokenA, ['channel' => 'email', 'body' => 'm', 'recipients' => [['email' => 'a@a.com']]]);
        $provider = $this->withHeaders(['Authorization' => 'Bearer ' . $tokenA])->withBodyFormat('json')->post('/api/v1/communication-providers/', [
            'channel' => 'email',
            'provider_name' => 'smtp-a',
            'is_default' => true,
        ]);
        $providerId = (int) json_decode($provider->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data']['id'];

        $this->withHeaders(['Authorization' => 'Bearer ' . $tokenB])->get('/api/v1/notification-templates/' . $tplId)->assertStatus(403);
        $this->withHeaders(['Authorization' => 'Bearer ' . $tokenB])->get('/api/v1/notification-messages/' . (int) $msg['id'])->assertStatus(403);
        $this->withHeaders(['Authorization' => 'Bearer ' . $tokenB])->get('/api/v1/communication-providers/' . $providerId)->assertStatus(403);
    }

    /**
     * @param array<string,mixed> $payload
     * @return array<string,mixed>
     */
    private function createMessage(string $token, array $payload): array
    {
        $res = $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/notification-messages/', $payload);
        $res->assertStatus(200);
        return json_decode($res->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data'];
    }

    /**
     * @return array{0:string,1:int}
     */
    private function createTenantAdmin(string $email): array
    {
        $db = Database::connect();
        $now = date('Y-m-d H:i:s');
        $db->table('companies')->insert([
            'public_id' => $this->uuid(),
            'name' => 'Notif Co ' . bin2hex(random_bytes(2)),
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $companyId = (int) $db->insertID();
        $password = 'Password123!';
        $db->table('users')->insert([
            'company_id' => $companyId,
            'public_id' => $this->uuid(),
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'first_name' => 'Notif',
            'last_name' => 'Admin',
            'status' => 'active',
            'is_active' => 1,
            'failed_login_count' => 0,
            'locked_until' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $userId = (int) $db->insertID();
        $role = $db->table('roles')->where('company_id', null)->where('code', 'company_admin')->get()->getRowArray();
        $db->table('user_roles')->insert([
            'company_id' => $companyId,
            'user_id' => $userId,
            'role_id' => (int) ($role['id'] ?? 0),
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $login = $this->withBodyFormat('json')->post('/api/v1/auth/login', ['email' => $email, 'password' => $password]);
        $login->assertStatus(200);
        $token = (string) json_decode($login->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data']['access_token'];
        return [$token, $userId];
    }

    private function uuid(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
        $hex = bin2hex($bytes);
        return sprintf('%s-%s-%s-%s-%s', substr($hex, 0, 8), substr($hex, 8, 4), substr($hex, 12, 4), substr($hex, 16, 4), substr($hex, 20, 12));
    }
}
