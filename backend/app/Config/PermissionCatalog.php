<?php

namespace Config;

use App\Exceptions\InvalidPermissionCodeException;
use App\Exceptions\PermissionNotFoundException;
use CodeIgniter\Config\BaseConfig;

class PermissionCatalog extends BaseConfig
{
    private const CODE_REGEX = '/^[a-z]+\.[a-z]+(\.[a-z]+)*$/';

    /**
     * @var list<array{code:string,label:string,scope:string,description:string,is_active:bool}>
     */
    private array $permissions = [
        [
            'code' => 'auth.me.view',
            'label' => 'Auth Me View',
            'scope' => 'company',
            'description' => 'Authenticated kullanici profilini goruntuleme izni',
            'is_active' => true,
        ],
        [
            'code' => 'auth.logout',
            'label' => 'Auth Logout',
            'scope' => 'company',
            'description' => 'Aktif oturumu sonlandirma izni',
            'is_active' => true,
        ],
        [
            'code' => 'auth.session.list',
            'label' => 'Auth Session List',
            'scope' => 'company',
            'description' => 'Kullanicinin oturumlarini listeleme izni',
            'is_active' => true,
        ],
        [
            'code' => 'auth.session.revoke',
            'label' => 'Auth Session Revoke',
            'scope' => 'company',
            'description' => 'Belirli bir oturumu sonlandirma izni',
            'is_active' => true,
        ],
        [
            'code' => 'auth.session.revoke.all',
            'label' => 'Auth Session Revoke All',
            'scope' => 'company',
            'description' => 'Tum oturumlari sonlandirma izni',
            'is_active' => true,
        ],
        [
            'code' => 'profile.view',
            'label' => 'Profile View',
            'scope' => 'company',
            'description' => 'Kullanici profilini goruntuleme izni',
            'is_active' => true,
        ],
        [
            'code' => 'profile.update',
            'label' => 'Profile Update',
            'scope' => 'company',
            'description' => 'Kullanici profilini guncelleme izni',
            'is_active' => true,
        ],
        [
            'code' => 'profile.password.change',
            'label' => 'Profile Password Change',
            'scope' => 'company',
            'description' => 'Kullanici sifresi degistirme izni',
            'is_active' => true,
        ],
        [
            'code' => 'user.role.assign',
            'label' => 'User Role Assign',
            'scope' => 'company',
            'description' => 'Kullaniciya rol atama izni',
            'is_active' => true,
        ],
        [
            'code' => 'user.role.revoke',
            'label' => 'User Role Revoke',
            'scope' => 'company',
            'description' => 'Kullanicidan rol kaldirma izni',
            'is_active' => true,
        ],
        [
            'code' => 'permission.manage',
            'label' => 'Permission Manage',
            'scope' => 'system',
            'description' => 'Permission envanteri yonetim izni',
            'is_active' => true,
        ],
    ];

    public function __construct()
    {
        parent::__construct();
        $this->validateCatalog();
    }

    /**
     * @return list<array{code:string,label:string,scope:string,description:string,is_active:bool}>
     */
    public function all(): array
    {
        return $this->permissions;
    }

    /**
     * @return list<string>
     */
    public function codes(): array
    {
        return array_column($this->permissions, 'code');
    }

    public function exists(string $code): bool
    {
        $normalized = strtolower(trim($code));
        foreach ($this->permissions as $permission) {
            if ($permission['code'] === $normalized) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array{code:string,label:string,scope:string,description:string,is_active:bool}
     */
    public function get(string $code): array
    {
        $normalized = strtolower(trim($code));
        foreach ($this->permissions as $permission) {
            if ($permission['code'] === $normalized) {
                return $permission;
            }
        }

        throw new PermissionNotFoundException('Permission bulunamadi: ' . $code);
    }

    public function scopeOf(string $code): string
    {
        return $this->get($code)['scope'];
    }

    public function assertExists(string $code): void
    {
        $normalized = trim($code);
        if (! preg_match(self::CODE_REGEX, $normalized)) {
            throw new InvalidPermissionCodeException('Permission kodu formati gecersiz: ' . $code);
        }

        if ($normalized !== strtolower($normalized)) {
            throw new InvalidPermissionCodeException('Permission kodu lowercase olmali: ' . $code);
        }

        if (! $this->exists($normalized)) {
            throw new PermissionNotFoundException('Permission bulunamadi: ' . $code);
        }
    }

    public function validateCatalog(): void
    {
        $seen = [];

        foreach ($this->permissions as $idx => $permission) {
            $this->validateRequiredKeys($permission, $idx);

            $code = trim((string) $permission['code']);
            if ($code !== strtolower($code)) {
                throw new InvalidPermissionCodeException('Permission kodu lowercase olmali: ' . $code);
            }

            if (! preg_match(self::CODE_REGEX, $code)) {
                throw new InvalidPermissionCodeException('Permission kodu regex formatina uymuyor: ' . $code);
            }

            if (isset($seen[$code])) {
                throw new InvalidPermissionCodeException('Duplicate permission kodu: ' . $code);
            }
            $seen[$code] = true;

            $scope = trim((string) $permission['scope']);
            if (! in_array($scope, ['system', 'company'], true)) {
                throw new InvalidPermissionCodeException('Permission scope gecersiz: ' . $scope);
            }
        }
    }

    /**
     * @param array<string, mixed> $permission
     */
    private function validateRequiredKeys(array $permission, int $idx): void
    {
        foreach (['code', 'label', 'scope', 'description', 'is_active'] as $key) {
            if (! array_key_exists($key, $permission)) {
                throw new InvalidPermissionCodeException('Permission kaydi eksik alan: ' . $key . ' [index=' . $idx . ']');
            }
        }
    }
}

