<?php

namespace App\Services\Auth;

use App\Models\PermissionModel;
use Config\PermissionCatalog;

class PermissionMatrixService
{
    private string $routesPath;
    private PermissionCatalog $permissionCatalog;
    private PermissionModel $permissionModel;

    public function __construct(
        ?PermissionCatalog $permissionCatalog = null,
        ?PermissionModel $permissionModel = null,
        ?string $routesPath = null
    ) {
        $this->permissionCatalog = $permissionCatalog ?? new PermissionCatalog();
        $this->permissionModel = $permissionModel ?? new PermissionModel();
        $this->routesPath = $routesPath ?? ROOTPATH . 'app/Config/Routes.php';
    }

    /**
     * @return array{valid:bool,errors:list<string>,warnings:list<string>}
     */
    public function validateCatalogAgainstDatabase(): array
    {
        $catalogRows = $this->permissionCatalog->all();
        $catalogCodes = [];
        $activeCatalogCodes = [];

        foreach ($catalogRows as $row) {
            $code = (string) ($row['code'] ?? '');
            if ($code === '') {
                continue;
            }
            $catalogCodes[$code] = true;
            if ((bool) ($row['is_active'] ?? false)) {
                $activeCatalogCodes[$code] = true;
            }
        }

        $dbRows = $this->permissionModel->getAllPermissionsForMatrix();
        $dbCodes = [];
        $errors = [];
        $warnings = [];

        foreach ($dbRows as $row) {
            $dbCode = strtolower(trim((string) ($row['code'] ?? '')));
            if ($dbCode === '') {
                continue;
            }

            $dbCodes[$dbCode] = true;
            if (! isset($catalogCodes[$dbCode])) {
                $errors[] = 'DB permission katalogda tanimli degil: ' . $dbCode;
            }
        }

        foreach (array_keys($activeCatalogCodes) as $catalogCode) {
            if (! isset($dbCodes[$catalogCode])) {
                $warnings[] = 'Aktif katalog permission DB tarafinda bulunamadi: ' . $catalogCode;
            }
        }

        return [
            'valid' => $errors === [],
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    /**
     * @return array{valid:bool,errors:list<string>,warnings:list<string>}
     */
    public function validateRolePermissionMatrix(): array
    {
        $rows = $this->permissionModel->getRolePermissionMatrixRows();
        $errors = [];
        $warnings = [];

        foreach ($rows as $row) {
            $rolePermissionActive = (int) ($row['role_permission_active'] ?? 0) === 1;
            if (! $rolePermissionActive) {
                continue;
            }

            $permissionCode = isset($row['permission_code']) ? strtolower(trim((string) $row['permission_code'])) : '';
            if ($permissionCode === '') {
                $errors[] = 'role_permissions kaydi tanimsiz permission_id referansi iceriyor: ' . (string) ($row['permission_id'] ?? '');
                continue;
            }

            $permissionActive = (int) ($row['permission_active'] ?? 0) === 1;
            if (! $permissionActive) {
                $errors[] = 'role_permissions kaydi pasif permissiona bagli: ' . $permissionCode;
            }

            $deprecatedAt = $row['permission_deprecated_at'] ?? null;
            if ($deprecatedAt !== null && $deprecatedAt !== '') {
                $warnings[] = 'role_permissions kaydi deprecated permission kullaniyor: ' . $permissionCode;
            }
        }

        return [
            'valid' => $errors === [],
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    public function assertPermissionKnown(string $permissionCode): void
    {
        $this->permissionCatalog->assertExists(strtolower(trim($permissionCode)));
    }

    /**
     * @return array{valid:bool,errors:list<string>,warnings:list<string>}
     */
    public function validateRoutePermissionCoverage(): array
    {
        $content = (string) file_get_contents($this->routesPath);
        $errors = [];

        // Non-greedy `[^\n]+?\[` so we anchor at the route row's option array (`[..., [`),
        // not at an inner `['filter' => [` bracket on the same line (would blind-match a later group).
        preg_match_all(
            '/\$routes->(?:get|post|put|patch|delete)\([^\n]+?\[(?:.|\n)*?\'filter\'\s*=>\s*\[(.*?)\](?:.|\n)*?\]\);/m',
            $content,
            $matches
        );

        $filterBlocks = $matches[1] ?? [];
        foreach ($filterBlocks as $filterBlock) {
            $hasAuthToken = str_contains($filterBlock, "'auth-token'");
            $hasActiveUser = str_contains($filterBlock, "'active-user'");
            $hasPermission = str_contains($filterBlock, "'permission:");

            if ($hasAuthToken && $hasActiveUser && ! $hasPermission) {
                $errors[] = 'Protected route permission filtresi eksik: ' . trim($filterBlock);
            }
        }

        return [
            'valid' => $errors === [],
            'errors' => $errors,
            'warnings' => [],
        ];
    }

    /**
     * @return array{valid:bool,errors:list<string>,warnings:list<string>}
     */
    public function validateAll(): array
    {
        $catalogCheck = $this->validateCatalogAgainstDatabase();
        $matrixCheck = $this->validateRolePermissionMatrix();
        $routeCheck = $this->validateRoutePermissionCoverage();

        return [
            'valid' => $catalogCheck['valid'] && $matrixCheck['valid'] && $routeCheck['valid'],
            'errors' => array_values(array_merge($catalogCheck['errors'], $matrixCheck['errors'], $routeCheck['errors'])),
            'warnings' => array_values(array_merge($catalogCheck['warnings'], $matrixCheck['warnings'], $routeCheck['warnings'])),
        ];
    }
}

