import { useState } from 'react'
import { Link } from 'react-router-dom'

import {
  useCloseDuePeriodMutation,
  useDeleteDuePeriodMutation,
  useLockDuePeriodMutation,
} from '@/features/finance/hooks/useDuePeriodMutations'
import { useDuePeriodsQuery } from '@/features/finance/hooks/useDuePeriodsQuery'
import { FinanceStatusBadge } from '@/features/finance/components/FinanceStatusBadge'
import { ConfirmDialog } from '@/shared/components/ConfirmDialog'
import { EmptyState } from '@/shared/components/EmptyState'
import { PermissionDeniedNotice } from '@/shared/components/PermissionDeniedNotice'
import { useEffectiveCan } from '@/shared/hooks/useEffectiveCan'
import { useToast } from '@/shared/hooks/useToast'
import { getErrorMessage } from '@/shared/lib/extractValidationErrors'

export function DuePeriodsPage() {
  const canList = useEffectiveCan('due_period.list')
  const canCreate = useEffectiveCan('due_period.create')
  const canDelete = useEffectiveCan('due_period.delete')
  const canClose = useEffectiveCan('due_period.close')
  const canLock = useEffectiveCan('due_period.lock')
  const [page, setPage] = useState(1)
  const [deleteId, setDeleteId] = useState<number | null>(null)
  const toast = useToast()
  const query = useDuePeriodsQuery({ page, per_page: 10 }, canList)
  const del = useDeleteDuePeriodMutation()
  const closeMutation = useCloseDuePeriodMutation()
  const lockMutation = useLockDuePeriodMutation()

  if (!canList) return <PermissionDeniedNotice permission="due_period.list" />

  const items = query.data?.items ?? []
  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between gap-3">
        <h1 className="text-xl font-semibold">Due periods</h1>
        {canCreate ? (
          <Link to="/finance/due-periods/new" className="rounded-lg bg-violet-600 px-3 py-2 text-sm text-white">
            New due period
          </Link>
        ) : null}
      </div>
      {items.length === 0 ? (
        <EmptyState title="Due period yok" description="Yeni period olusturabilirsiniz." />
      ) : (
        <div className="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-800">
          <table className="min-w-full text-sm">
            <thead className="bg-zinc-100 dark:bg-zinc-900">
              <tr>
                <th className="px-3 py-2 text-left">ID</th>
                <th className="px-3 py-2 text-left">Period key</th>
                <th className="px-3 py-2 text-left">Dates</th>
                <th className="px-3 py-2 text-left">Status</th>
                <th className="px-3 py-2 text-left">Actions</th>
              </tr>
            </thead>
            <tbody>
              {items.map((row) => (
                <tr key={row.id} className="border-t border-zinc-200 dark:border-zinc-800">
                  <td className="px-3 py-2">{row.id}</td>
                  <td className="px-3 py-2">{row.period_key}</td>
                  <td className="px-3 py-2">
                    {row.start_date} - {row.end_date}
                  </td>
                  <td className="px-3 py-2"><FinanceStatusBadge status={row.status} /></td>
                  <td className="px-3 py-2">
                    <div className="flex flex-wrap gap-2">
                      <Link className="text-violet-600" to={`/finance/due-periods/${row.id}`}>
                        Open
                      </Link>
                      <Link className="text-violet-600" to={`/finance/due-periods/${row.id}/edit`}>
                        Edit
                      </Link>
                      {canClose ? (
                        <button
                          type="button"
                          className="text-amber-600"
                          onClick={() =>
                            closeMutation.mutate(row.id, {
                              onSuccess: () => toast.success('Due period kapatildi.'),
                              onError: (err) => toast.error(getErrorMessage(err)),
                            })
                          }
                        >
                          Close
                        </button>
                      ) : null}
                      {canLock ? (
                        <button
                          type="button"
                          className="text-orange-600"
                          onClick={() =>
                            lockMutation.mutate(row.id, {
                              onSuccess: () => toast.success('Due period kilitlendi.'),
                              onError: (err) => toast.error(getErrorMessage(err)),
                            })
                          }
                        >
                          Lock
                        </button>
                      ) : null}
                      {canDelete ? (
                        <button type="button" className="text-red-600" onClick={() => setDeleteId(row.id)}>
                          Delete
                        </button>
                      ) : null}
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
      <div className="flex items-center gap-2">
        <button type="button" className="rounded border px-2 py-1 text-sm" disabled={page <= 1} onClick={() => setPage((p) => p - 1)}>Prev</button>
        <span className="text-sm">Page {page}</span>
        <button type="button" className="rounded border px-2 py-1 text-sm" disabled={(query.data?.items?.length ?? 0) < 10} onClick={() => setPage((p) => p + 1)}>Next</button>
      </div>
      <ConfirmDialog
        isOpen={deleteId !== null}
        title="Due period silinsin mi?"
        description="Bu islem geri alinamaz."
        confirmText={del.isPending ? 'Deleting…' : 'Delete'}
        cancelText="Vazgec"
        variant="danger"
        onClose={() => setDeleteId(null)}
        onConfirm={() => {
          if (deleteId === null) return
          del.mutate(deleteId, {
            onSuccess: () => {
              toast.success('Due period silindi.')
              setDeleteId(null)
            },
            onError: (err) => toast.error(getErrorMessage(err)),
          })
        }}
      />
    </div>
  )
}
