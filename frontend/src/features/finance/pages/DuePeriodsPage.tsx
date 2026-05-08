import { useState } from 'react'
import { useTranslation } from 'react-i18next'
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
  const { t } = useTranslation(['finance', 'common'])
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
        <h1 className="text-xl font-semibold">{t('duePeriods', { ns: 'finance' })}</h1>
        {canCreate ? (
          <Link to="/finance/due-periods/new" className="rounded-lg bg-violet-600 px-3 py-2 text-sm text-white">
            {t('create', { ns: 'common' })}
          </Link>
        ) : null}
      </div>
      {items.length === 0 ? (
        <EmptyState title={t('noDuePeriod', { ns: 'finance' })} description={t('emptyDescription', { ns: 'common' })} />
      ) : (
        <div className="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-800">
          <table className="min-w-full text-sm">
            <thead className="bg-zinc-100 dark:bg-zinc-900">
              <tr>
                <th className="px-3 py-2 text-left">ID</th>
                <th className="px-3 py-2 text-left">{t('periodKey', { ns: 'finance' })}</th>
                <th className="px-3 py-2 text-left">{t('dates', { ns: 'finance' })}</th>
                <th className="px-3 py-2 text-left">{t('status', { ns: 'finance' })}</th>
                <th className="px-3 py-2 text-left">{t('actions', { ns: 'finance' })}</th>
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
                        {t('open', { ns: 'finance' })}
                      </Link>
                      <Link className="text-violet-600" to={`/finance/due-periods/${row.id}/edit`}>
                        {t('edit', { ns: 'finance' })}
                      </Link>
                      {canClose ? (
                        <button
                          type="button"
                          className="text-amber-600"
                          onClick={() =>
                            closeMutation.mutate(row.id, {
                              onSuccess: () => toast.success(t('closeSuccess', { ns: 'finance' })),
                              onError: (err) => toast.error(getErrorMessage(err)),
                            })
                          }
                        >
                          {t('close', { ns: 'finance' })}
                        </button>
                      ) : null}
                      {canLock ? (
                        <button
                          type="button"
                          className="text-orange-600"
                          onClick={() =>
                            lockMutation.mutate(row.id, {
                              onSuccess: () => toast.success(t('lockSuccess', { ns: 'finance' })),
                              onError: (err) => toast.error(getErrorMessage(err)),
                            })
                          }
                        >
                          {t('lock', { ns: 'finance' })}
                        </button>
                      ) : null}
                      {canDelete ? (
                        <button type="button" className="text-red-600" onClick={() => setDeleteId(row.id)}>
                          {t('delete', { ns: 'finance' })}
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
        <button type="button" className="rounded border px-2 py-1 text-sm" disabled={page <= 1} onClick={() => setPage((p) => p - 1)}>{t('pagination.prev', { ns: 'common' })}</button>
        <span className="text-sm">{t('page', { ns: 'finance' })} {page}</span>
        <button type="button" className="rounded border px-2 py-1 text-sm" disabled={(query.data?.items?.length ?? 0) < 10} onClick={() => setPage((p) => p + 1)}>{t('pagination.next', { ns: 'common' })}</button>
      </div>
      <ConfirmDialog
        isOpen={deleteId !== null}
        title={t('deleteConfirmTitle', { ns: 'finance' })}
        description={t('confirm', { ns: 'common' })}
        confirmText={del.isPending ? t('deleting', { ns: 'finance' }) : t('delete', { ns: 'finance' })}
        cancelText={t('cancel', { ns: 'common' })}
        variant="danger"
        onClose={() => setDeleteId(null)}
        onConfirm={() => {
          if (deleteId === null) return
          del.mutate(deleteId, {
            onSuccess: () => {
              toast.success(t('deleteSuccess', { ns: 'finance' }))
              setDeleteId(null)
            },
            onError: (err) => toast.error(getErrorMessage(err)),
          })
        }}
      />
    </div>
  )
}
