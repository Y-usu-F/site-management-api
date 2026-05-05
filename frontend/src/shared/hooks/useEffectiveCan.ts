import { useMemo } from 'react'

import { useAuthStore } from '@/features/auth/auth.store'
import { normalizePermissionCode } from '@/features/auth/permissions'

export function isStrictPermissionsEnabled(): boolean {
  return import.meta.env.VITE_STRICT_PERMISSIONS === 'true'
}

/**
 * Respects `VITE_STRICT_PERMISSIONS`:
 * - strict: requires the permission in `/auth/me`.
 * - non-strict: if permission array is empty (common in dev), allow access so screens stay usable.
 */
export function useEffectiveCan(permission: string): boolean {
  const permissions = useAuthStore((s) => s.permissions)
  return useMemo(() => {
    const key = normalizePermissionCode(permission)
    if (permissions.includes(key)) return true
    if (!isStrictPermissionsEnabled() && permissions.length === 0) return true
    return false
  }, [permission, permissions])
}
