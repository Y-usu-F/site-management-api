import { useAuthStore } from '@/features/auth/auth.store'

export function normalizePermissionCode(code: string): string {
  return code.trim().toLowerCase()
}

/** Imperative permission check (matches backend lowercase permission codes). */
export function can(permission: string): boolean {
  const codes = useAuthStore.getState().permissions
  const key = normalizePermissionCode(permission)
  return codes.includes(key)
}
