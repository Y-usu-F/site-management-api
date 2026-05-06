import { useNavigate } from 'react-router-dom'

import { logoutRequest } from '@/features/auth/auth.api'
import { useAuthStore } from '@/features/auth/auth.store'
import { NotificationBell } from '@/features/communication/NotificationBell'

export function Topbar() {
  const navigate = useNavigate()
  const user = useAuthStore((s) => s.user)
  const clearSession = useAuthStore((s) => s.clearSession)

  async function handleLogout() {
    try {
      await logoutRequest()
    } catch {
      // Still clear local session if token revoked server-side.
    } finally {
      clearSession()
      navigate('/login', { replace: true })
    }
  }

  return (
    <header className="flex h-14 shrink-0 items-center justify-between border-b border-zinc-200 bg-white px-6 dark:border-zinc-800 dark:bg-zinc-950">
      <span className="text-sm text-zinc-500 dark:text-zinc-400">
        Signed in as{' '}
        <span className="font-medium text-zinc-900 dark:text-zinc-100">
          {user?.email ?? '—'}
        </span>
      </span>
      <div className="flex items-center gap-2">
        <NotificationBell />
        <button
          type="button"
          onClick={() => void handleLogout()}
          className="rounded-lg border border-zinc-300 px-3 py-1.5 text-sm font-medium text-zinc-700 transition hover:bg-zinc-50 dark:border-zinc-600 dark:text-zinc-300 dark:hover:bg-zinc-900"
        >
          Log out
        </button>
      </div>
    </header>
  )
}
