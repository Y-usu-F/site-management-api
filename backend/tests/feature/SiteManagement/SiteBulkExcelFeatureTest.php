<?php

namespace Tests\Feature\SiteManagement;

use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Database;
use App\Services\Site\SiteService;
use App\Services\Common\ExcelImportService;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\Support\FeatureDatabaseTestCase;

final class SiteBulkExcelFeatureTest extends FeatureDatabaseTestCase
{
    use FeatureTestTrait;
    use DatabaseTestTrait;

    public function testBulkDeleteRequiresPermission(): void
    {
        [$email] = $this->createUserWithRole('site.bulk.employee@example.com', 'Password123!', 'employee');
        $access = (string) $this->login($email, 'Password123!')['data']['access_token'];

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $access])
            ->withBodyFormat('json')
            ->delete('/api/v1/sites/bulk', ['ids' => [1]]);

        $response->assertStatus(403);
    }

    public function testBulkDeleteTenantSafe(): void
    {
        [$ownerEmail] = $this->createUserWithRole('owner.bulk@example.com', 'Password123!', 'company_admin');
        [$attackerEmail] = $this->createUserWithRole('attacker.bulk@example.com', 'Password123!', 'company_admin');
        $ownerAccess = (string) $this->login($ownerEmail, 'Password123!')['data']['access_token'];
        $attackerAccess = (string) $this->login($attackerEmail, 'Password123!')['data']['access_token'];
        $siteId = $this->createSiteViaApi($ownerAccess, 'Owner Site', 'OWNER-BULK-1');

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $attackerAccess])
            ->withBodyFormat('json')
            ->delete('/api/v1/sites/bulk', ['ids' => [$siteId]]);

        $response->assertStatus(403);
    }

    public function testExportReturnsXlsxContentType(): void
    {
        [$email] = $this->createUserWithRole('site.export@example.com', 'Password123!', 'company_admin');
        $access = (string) $this->login($email, 'Password123!')['data']['access_token'];
        $this->createSiteViaApi($access, 'Export Site', 'EXPORT-1');

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $access])->get('/api/v1/sites/export');
        $response->assertStatus(200);
        $this->assertStringContainsString(
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            strtolower($response->response()->getHeaderLine('Content-Type'))
        );
    }

    public function testTemplateReturnsXlsxHeaders(): void
    {
        [$email] = $this->createUserWithRole('site.template@example.com', 'Password123!', 'company_admin');
        $access = (string) $this->login($email, 'Password123!')['data']['access_token'];

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $access])->get('/api/v1/sites/import-template');
        $response->assertStatus(200);
        $tempPath = tempnam(sys_get_temp_dir(), 'site_template_');
        file_put_contents($tempPath, $response->response()->getBody());
        $spreadsheet = IOFactory::load($tempPath);
        $sheet = $spreadsheet->getSheet(0);
        $headerRow = $sheet->rangeToArray('A1:D1', null, true, true, true)[1] ?? [];

        $this->assertSame('name', strtolower((string) ($headerRow['A'] ?? '')));
        $this->assertSame('code', strtolower((string) ($headerRow['B'] ?? '')));
        $this->assertSame('address', strtolower((string) ($headerRow['C'] ?? '')));
        $this->assertSame('status', strtolower((string) ($headerRow['D'] ?? '')));

        $spreadsheet->disconnectWorksheets();
        @unlink($tempPath);
    }

    public function testImportRejectsNonXlsx(): void
    {
        [$email] = $this->createUserWithRole('site.import.reject@example.com', 'Password123!', 'company_admin');
        $access = (string) $this->login($email, 'Password123!')['data']['access_token'];

        $tempPath = tempnam(sys_get_temp_dir(), 'site_bad_');
        file_put_contents($tempPath, 'plain-text');
        $_FILES['file'] = [
            'name' => 'bad.txt',
            'type' => 'text/plain',
            'tmp_name' => $tempPath,
            'error' => UPLOAD_ERR_OK,
            'size' => filesize($tempPath),
        ];
        service('superglobals')->setFilesArray($_FILES);

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $access])->post('/api/v1/sites/import');
        $response->assertStatus(422);

        unset($_FILES['file']);
        service('superglobals')->setFilesArray($_FILES);
        @unlink($tempPath);
    }

    public function testImportServiceReturnsRowErrorsAndInsertsValidRows(): void
    {
        [$email] = $this->createUserWithRole('site.import.valid@example.com', 'Password123!', 'company_admin');
        $access = (string) $this->login($email, 'Password123!')['data']['access_token'];
        $this->withHeaders(['Authorization' => 'Bearer ' . $access])->get('/api/v1/auth/me')->assertStatus(200);

        $tempPath = $this->buildImportExcel([
            ['name' => 'Valid Site', 'code' => 'VALID-IMPORT-1', 'address' => 'Adres', 'status' => 'active'],
            ['name' => '', 'code' => '', 'address' => 'Eksik', 'status' => 'active'],
        ]);

        $file = new \CodeIgniter\HTTP\Files\UploadedFile(
            $tempPath,
            'sites.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            UPLOAD_ERR_OK,
            true
        );
        $payload = (new SiteService())->importRows(new ExcelImportService(), $file);

        $this->assertSame(1, (int) $payload['inserted_count']);
        $this->assertSame(1, (int) $payload['skipped_count']);
        $this->assertNotEmpty($payload['error_rows']);

        $db = Database::connect();
        $site = $db->table('sites')->where('code', 'VALID-IMPORT-1')->get()->getRowArray();
        $this->assertIsArray($site);

        @unlink($tempPath);
    }

    private function buildImportExcel(array $rows): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $headers = ['name', 'code', 'address', 'status'];
        $col = 1;
        foreach ($headers as $header) {
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($col) . '1', $header);
            $col++;
        }

        $rowNumber = 2;
        foreach ($rows as $row) {
            $sheet->setCellValue('A' . $rowNumber, (string) ($row['name'] ?? ''));
            $sheet->setCellValue('B' . $rowNumber, (string) ($row['code'] ?? ''));
            $sheet->setCellValue('C' . $rowNumber, (string) ($row['address'] ?? ''));
            $sheet->setCellValue('D' . $rowNumber, (string) ($row['status'] ?? 'active'));
            $rowNumber++;
        }

        $path = tempnam(sys_get_temp_dir(), 'site_import_');
        $writer = new Xlsx($spreadsheet);
        $writer->save($path);
        $spreadsheet->disconnectWorksheets();
        return $path;
    }

    /**
     * @return array{0:string,1:int}
     */
    private function createUserWithRole(string $email, string $password, string $roleCode): array
    {
        $db = Database::connect();
        $now = date('Y-m-d H:i:s');

        $db->table('companies')->insert([
            'public_id' => $this->uuid(),
            'name' => 'Site Co ' . bin2hex(random_bytes(2)),
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $companyId = (int) $db->insertID();

        $db->table('users')->insert([
            'company_id' => $companyId,
            'public_id' => $this->uuid(),
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'first_name' => 'Site',
            'last_name' => 'User',
            'status' => 'active',
            'is_active' => 1,
            'failed_login_count' => 0,
            'locked_until' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $userId = (int) $db->insertID();

        $role = $db->table('roles')->where('company_id', null)->where('code', $roleCode)->get()->getRowArray();
        if ($role === null) {
            throw new \RuntimeException('Rol bulunamadi: ' . $roleCode);
        }
        $roleId = (int) $role['id'];

        $db->table('user_roles')->insert([
            'company_id' => $companyId,
            'user_id' => $userId,
            'role_id' => $roleId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return [$email, $userId];
    }

    private function login(string $email, string $password): array
    {
        $result = $this->withBodyFormat('json')->post('/api/v1/auth/login', ['email' => $email, 'password' => $password]);
        $result->assertStatus(200);
        return json_decode($result->getJSON(), true, 512, JSON_THROW_ON_ERROR);
    }

    private function createSiteViaApi(string $accessToken, string $name, string $code): int
    {
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $accessToken])
            ->withBodyFormat('json')
            ->post('/api/v1/sites/', ['name' => $name, 'code' => $code, 'address' => 'Adres']);
        $response->assertStatus(200);

        return (int) json_decode($response->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data']['id'];
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
