import { useMemo, useState } from 'react'
import { Link } from 'react-router-dom'
import { useTranslation } from 'react-i18next'

import {
  useMarkAllNotificationRecipientsReadMutation,
  useMarkNotificationRecipientReadMutation,
  useNotificationRecipientsQuery,
  useNotificationUnreadCountQuery,
} from '@/features/communication/notification.hooks'
import { useEffectiveCan } from '@/shared/hooks/useEffectiveCan'
import { useToast } from '@/shared/hooks/useToast'
import { getErrorMessage } from '@/shared/lib/extractValidationErrors'

function BellIcon({ className }: { className?: string }) {
  return (
    <svg
      viewBox="0 0 24 24"
      fill="none"
      stroke="currentColor"
      strokeWidth="2"
      className={className}
    >
      <path d="M18 8a6 6 0 10-12 0c0 7-3 7-3 7h18s-3 0-3-7" />
      <path d="M13.73 21a2 2 0 01-3.46 0" />
    </svg>
  )
}

export function NotificationBell() {
  const { t } = useTranslation(['common', 'notifications', 'navigation'])
  const toast = useToast()
  const canList = useEffectiveCan('notification_recipient.list')
  const canMarkRead = useEffectiveCan('notification_recipient.mark_read')
  const [open, setOpen] = useState(false)

  const list = useNotificationRecipientsQuery(
    { page: 1, per_page: 5, sort: 'created_at', direction: 'desc' },
    canList,
    30_000,
  )
  const unread = useNotificationUnreadCountQuery(canList, 30_000)
  const markRead = useMarkNotificationRecipientReadMutation()
  const markAllRead = useMarkAllNotificationRecipientsReadMutation()

  const items = useMemo(() => list.data?.items ?? [], [list.data?.items])
  const unreadCount = unread.data?.unread_count ?? 0

  async function handleMarkRead(id: number) {
    try {
      await markRead.mutateAsync(id)
      toast.success(t('markedRead', { ns: 'notifications' }))
    } catch (e) {
      toast.error(getErrorMessage(e))
    }
  }

  async function handleMarkAllRead() {
    try {
      const result = await markAllRead.mutateAsync()
      toast.success(t('markedAllRead', { ns: 'notifications', count: result.marked_count }))
    } catch (e) {
      toast.error(getErrorMessage(e))
    }
  }

  if (!canList) return null

  return (
    <div className="relative">
      <button
        type="button"
        onClick={() => setOpen((v) => !v)}
        className="relative rounded-lg border border-zinc-300 p-2 text-zinc-700 transition hover:bg-zinc-50 dark:border-zinc-600 dark:text-zinc-300 dark:hover:bg-zinc-900"
        aria-label={t('notifications', { ns: 'navigation' })}
      >
        <BellIcon className="h-5 w-5" />
        {unreadCount > 0 ? (
          <span className="absolute -right-1 -top-1 inline-flex min-w-5 items-center justify-center rounded-full bg-violet-600 px-1 text-xs font-semibold text-white">
            {unreadCount}
          </span>
        ) : null}
      </button>

      {open ? (
        <div className="absolute right-0 z-50 mt-2 w-96 overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-lg dark:border-zinc-800 dark:bg-zinc-950">
          <div className="flex items-center justify-between border-b border-zinc-200 px-4 py-3 text-sm font-semibold dark:border-zinc-800">
            <span>{t('title', { ns: 'notifications' })}</span>
            <div className="flex items-center gap-3">
              {canMarkRead ? (
                <button
                  type="button"
                  onClick={() => void handleMarkAllRead()}
                  disabled={unreadCount <= 0 || markAllRead.isPending}
                  className="text-xs font-medium text-zinc-600 hover:text-zinc-900 disabled:opacity-50 dark:text-zinc-300 dark:hover:text-zinc-100"
                >
                  {t('markAllRead', { ns: 'common' })}
                </button>
              ) : null}
              <Link
                to="/communication/notifications"
                className="text-xs font-medium text-violet-600 hover:underline"
                onClick={() => setOpen(false)}
              >
                {t('viewAll', { ns: 'common' })}
              </Link>
            </div>
          </div>

          {list.isLoading ? (
            <div className="px-4 py-3 text-sm text-zinc-500">{t('loading', { ns: 'common' })}</div>
          ) : list.isError ? (
            <div className="px-4 py-3 text-sm text-red-600">{t('listFailed', { ns: 'notifications' })}</div>
          ) : items.length === 0 ? (
            <div className="px-4 py-3 text-sm text-zinc-500">{t('emptyDescription', { ns: 'notifications' })}</div>
          ) : (
            <div className="divide-y divide-zinc-100 dark:divide-zinc-900">
              {items.map((n) => {
                const isUnread = !n.read_at
                return (
                <div
                  key={n.id}
                  className={[
                    'flex items-start justify-between gap-3 px-4 py-3',
                    isUnread
                      ? 'bg-violet-50/60 dark:bg-violet-950/20'
                      : '',
                  ].join(' ')}
                >
                  <div className="min-w-0">
                    <div
                      className={[
                        'truncate text-sm text-zinc-900 dark:text-zinc-100',
                        isUnread ? 'font-semibold' : 'font-medium',
                      ].join(' ')}
                    >
                      {t('notificationLabel', { ns: 'notifications' })} #{n.id}
                    </div>
                    <div className="mt-0.5 text-xs text-zinc-500">
                      message_id: {n.message_id} • {n.read_at ? t('status.read', { ns: 'common' }) : t('status.unread', { ns: 'common' })}
                    </div>
                  </div>
                  {!n.read_at && canMarkRead ? (
                    <button
                      type="button"
                      onClick={() => void handleMarkRead(n.id)}
                      disabled={markRead.isPending}
                      className="shrink-0 rounded-lg border border-zinc-300 px-2 py-1 text-xs font-medium text-zinc-700 hover:bg-zinc-50 disabled:opacity-60 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-900"
                    >
                      {t('status.read', { ns: 'common' })}
                    </button>
                  ) : null}
                </div>
                )
              })}
            </div>
          )}
        </div>
      ) : null}
    </div>
  )
}

