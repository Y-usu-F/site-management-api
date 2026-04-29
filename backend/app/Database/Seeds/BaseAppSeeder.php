<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use RuntimeException;

abstract class BaseAppSeeder extends Seeder
{
    protected function logStart(string $seederName): void
    {
        log_message('info', sprintf('[seed] %s started (env=%s)', $seederName, ENVIRONMENT));
    }

    protected function logSuccess(string $seederName, string $message = ''): void
    {
        $suffix = $message !== '' ? ' - ' . $message : '';
        log_message('info', sprintf('[seed] %s success (env=%s)%s', $seederName, ENVIRONMENT, $suffix));
    }

    protected function logFailure(string $seederName, string $message): void
    {
        log_message('error', sprintf('[seed] %s failed (env=%s) - %s', $seederName, ENVIRONMENT, $message));
    }

    protected function requireEnv(string $key): string
    {
        $value = env($key);
        if (! is_string($value) || trim($value) === '') {
            throw new RuntimeException(sprintf('Required environment variable "%s" is missing or empty.', $key));
        }

        return trim($value);
    }

    protected function now(): string
    {
        return date('Y-m-d H:i:s');
    }

    protected function getSystemCompanyPublicId(): string
    {
        $publicId = env('SYSTEM_COMPANY_PUBLIC_ID');
        if (! is_string($publicId) || trim($publicId) === '') {
            return '00000000-0000-0000-0000-000000000001';
        }

        return trim($publicId);
    }

    protected function getSystemCompanyName(): string
    {
        $name = env('SYSTEM_COMPANY_NAME');
        if (! is_string($name) || trim($name) === '') {
            return 'System Company';
        }

        return trim($name);
    }

    protected function ensureSystemCompany(): array
    {
        $builder = $this->db->table('companies');
        $publicId = $this->getSystemCompanyPublicId();
        $companyName = $this->getSystemCompanyName();
        $now = $this->now();

        $company = $builder->where('public_id', $publicId)->get()->getRowArray();
        if ($company !== null) {
            return $company;
        }

        // Backward-compatible path: if tenant.defaultCompanyId exists, that record is reused
        // and normalized with configured system public_id to keep seeds idempotent.
        $defaultCompanyId = (int) env('tenant.defaultCompanyId');
        if ($defaultCompanyId > 0) {
            $existingById = $builder->where('id', $defaultCompanyId)->get()->getRowArray();
            if ($existingById !== null) {
                $builder->where('id', $defaultCompanyId)->update([
                    'public_id'  => $publicId,
                    'name'       => $existingById['name'] ?: $companyName,
                    'status'     => $existingById['status'] ?: 'active',
                    'updated_at' => $now,
                ]);

                return $builder->where('id', $defaultCompanyId)->get()->getRowArray() ?? [];
            }
        }

        $builder->insert([
            'public_id'  => $publicId,
            'name'       => $companyName,
            'status'     => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return $builder->where('public_id', $publicId)->get()->getRowArray() ?? [];
    }

    protected function generateUuidV4(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
    }
}
