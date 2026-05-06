import { useMemo, useState } from 'react'

import {
  useMarkAllNotificationRecipientsReadMutation,
  useMarkNotificationRecipientReadMutation,
  useNotificationRecipientsQuery,
  useNotificationUnreadCountQuery,
} from '@/features/communication/notification.hooks'
import { EmptyState } from '@/shared/components/EmptyState'
import { PermissionDeniedNotice } from '@/shared/components/PermissionDeniedNotice'
import { useEffectiveCan } from '@/shared/hooks/useEffectiveCan'
import { useToast } from '@/shared/hooks/useToast'
import { getErrorMessage } from '@/shared/lib/extractValidationErrors'

export function NotificationsPage() {
  const toast = useToast()
  const canList = useEffectiveCan('notification_recipient.list')
  const canMarkRead = useEffectiveCan('notification_recipient.mark_read')

  const [page, setPage] = useState(1)
  const [status, setStatus] = useState<'all' | 'unread' | 'read'>('all')

  const list = useNotificationRecipientsQuery(
    {
      page,
      per_page: 20,
      sort: 'created_at',
      direction: 'desc',
      read_status: status === 'all' ? undefined : status,
    },
    canList,
    30_000,
  )
  const unread = useNotificationUnreadCountQuery(canList, 30_000)
  const markRead = useMarkNotificationRecipientReadMutation()
  const markAllRead = useMarkAllNotificationRecipientsReadMutation()

  const items = useMemo(() => list.data?.items ?? [], [list.data?.items])
  const unreadOnPage = useMemo(() => items.filter((x) => !x.read_at).length, [items])

  async function handleMarkRead(id: number) {
    try {
      await markRead.mutateAsync(id)
      toast.success('Okundu olarak isaretlendi')
    } catch (e) {
      toast.error(getErrorMessage(e))
    }
  }

  async function handleMarkAllRead() {
    try {
      const result = await markAllRead.mutateAsync()
      toast.success(`${result.marked_count} bildirim okundu olarak isaretlendi`)
    } catch (e) {
      toast.error(getErrorMessage(e))
    }
  }

  if (!canList) {
    return <PermissionDeniedNotice permission="notification_recipient.list" title="Notifications" />
  }

  return (
    <div className="space-y-4">
      <div className="flex items-start justify-between gap-4">
        <div>
          <h1 className="text-xl font-semibold">Notifications</h1>
          <p className="text-sm text-zinc-500">
            Sayfadaki okunmamis: <span className="font-medium text-zinc-900 dark:text-zinc-100">{unreadOnPage}</span>
          </p>
        </div>
        <div className="flex items-center gap-3">
          <div className="inline-flex rounded-lg border border-zinc-300 p-1 dark:border-zinc-700">
            {(['all', 'unread', 'read'] as const).map((value) => (
              <button
                key={value}
                type="button"
                onClick={() => {
                  setStatus(value)
                  setPage(1)
                }}
                className={[
                  'rounded-md px-3 py-1.5 text-xs font-medium transition',
                  status === value
                    ? 'bg-violet-600 text-white'
                    : 'text-zinc-600 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800',
                ].join(' ')}
              >
                {value === 'all' ? 'All' : value === 'unread' ? 'Unread' : 'Read'}
              </button>
            ))}
          </div>
          {canMarkRead ? (
            <button
              type="button"
              onClick={() => void handleMarkAllRead()}
              disabled={(unread.data?.unread_count ?? 0) <= 0 || markAllRead.isPending}
              className="rounded-lg border border-zinc-300 px-3 py-2 text-xs font-medium text-zinc-700 hover:bg-zinc-50 disabled:cursor-not-allowed disabled:opacity-60 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-900"
            >
              Mark all read
            </button>
          ) : null}
        </div>
      </div>

      {list.isLoading ? (
        <div className="text-sm text-zinc-500">Yukleniyor...</div>
      ) : list.isError ? (
        <div className="text-sm text-red-600">Bildirimler alinamadi.</div>
      ) : items.length === 0 ? (
        <EmptyState title="Bildirim bulunamadi" description="Henuz bir bildirim yok." />
      ) : (
        <div className="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
          <table className="w-full text-left text-sm">
            <thead className="bg-zinc-50 text-xs uppercase tracking-wide text-zinc-500 dark:bg-zinc-950">
              <tr>
                <th className="px-3 py-2">ID</th>
                <th className="px-3 py-2">Message</th>
                <th className="px-3 py-2">Status</th>
                <th className="px-3 py-2">Read</th>
                <th className="px-3 py-2">Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-zinc-100 dark:divide-zinc-800">
              {items.map((n) => (
                <tr
                  key={n.id}
                  className={!n.read_at ? 'bg-violet-50/40 dark:bg-violet-950/15' : undefined}
                >
                  <td className="px-3 py-2 font-medium">{n.id}</td>
                  <td className="px-3 py-2">#{n.message_id}</td>
                  <td className="px-3 py-2">{n.status ?? '-'}</td>
                  <td className="px-3 py-2">
                    {n.read_at ? (
                      'Okundu'
                    ) : (
                      <span className="inline-flex items-center gap-2">
                        <span className="h-2 w-2 rounded-full bg-violet-600" />
                        <span className="font-semibold text-violet-700 dark:text-violet-300">Okunmadi</span>
                      </span>
                    )}
                  </td>
                  <td className="px-3 py-2">
                    {!n.read_at && canMarkRead ? (
                      <button
                        type="button"
                        onClick={() => void handleMarkRead(n.id)}
                        disabled={markRead.isPending}
                        className="rounded-lg border border-zinc-300 px-2 py-1 text-xs font-medium text-zinc-700 hover:bg-zinc-50 disabled:opacity-60 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-950"
                      >
                        Okundu isaretle
                      </button>
                    ) : (
                      <span className="text-xs text-zinc-500">—</span>
                    )}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
          <div className="flex items-center justify-between border-t border-zinc-200 px-3 py-2 text-xs text-zinc-500 dark:border-zinc-800">
            <span>
              Toplam: <span className="font-medium text-zinc-900 dark:text-zinc-100">{list.data?.total ?? 0}</span>
            </span>
            <div className="flex items-center gap-2">
              <button
                type="button"
                onClick={() => setPage((p) => Math.max(1, p - 1))}
                disabled={page <= 1}
                className="rounded border border-zinc-300 px-2 py-1 disabled:opacity-60 dark:border-zinc-700"
              >
                Prev
              </button>
              <span>Page {page}</span>
              <button
                type="button"
                onClick={() => setPage((p) => p + 1)}
                disabled={items.length === 0}
                className="rounded border border-zinc-300 px-2 py-1 disabled:opacity-60 dark:border-zinc-700"
              >
                Next
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  )
}

