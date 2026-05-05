import { useMemo } from 'react'

import { useAuthStore } from '@/features/auth/auth.store'
import { normalizePermissionCode } from '@/features/auth/permissions'

export function useCan(permission: string): boolean {
  const permissions = useAuthStore((s) => s.permissions)
  return useMemo(() => {
    const key = normalizePermissionCode(permission)
    return permissions.includes(key)
  }, [permission, permissions])
}
