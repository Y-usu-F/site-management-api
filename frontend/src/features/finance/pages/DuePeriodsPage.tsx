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
        <h1 className="text-xl font-semibold">{t('finance.common.duePeriods')}</h1>
        {canCreate ? (
          <Link to="/finance/due-periods/new" className="rounded-lg bg-violet-600 px-3 py-2 text-sm text-white">
            {t('common.create')}
          </Link>
        ) : null}
      </div>
      {items.length === 0 ? (
        <EmptyState title={t('finance.common.noDuePeriod')} description={t('common.emptyDescription')} />
      ) : (
        <div className="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-800">
          <table className="min-w-full text-sm">
            <thead className="bg-zinc-100 dark:bg-zinc-900">
              <tr>
                <th className="px-3 py-2 text-left">ID</th>
                <th className="px-3 py-2 text-left">{t('finance.common.periodKey')}</th>
                <th className="px-3 py-2 text-left">{t('finance.common.dates')}</th>
                <th className="px-3 py-2 text-left">{t('finance.common.status')}</th>
                <th className="px-3 py-2 text-left">{t('finance.common.actions')}</th>
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
                        {t('finance.common.open')}
                      </Link>
                      <Link className="text-violet-600" to={`/finance/due-periods/${row.id}/edit`}>
                        {t('finance.common.edit')}
                      </Link>
                      {canClose ? (
                        <button
                          type="button"
                          className="text-amber-600"
                          onClick={() =>
                            closeMutation.mutate(row.id, {
                              onSuccess: () => toast.success(t('finance.common.closeSuccess')),
                              onError: (err) => toast.error(getErrorMessage(err)),
                            })
                          }
                        >
                          {t('finance.common.close')}
                        </button>
                      ) : null}
                      {canLock ? (
                        <button
                          type="button"
                          className="text-orange-600"
                          onClick={() =>
                            lockMutation.mutate(row.id, {
                              onSuccess: () => toast.success(t('finance.common.lockSuccess')),
                              onError: (err) => toast.error(getErrorMessage(err)),
                            })
                          }
                        >
                          {t('finance.common.lock')}
                        </button>
                      ) : null}
                      {canDelete ? (
                        <button type="button" className="text-red-600" onClick={() => setDeleteId(row.id)}>
                          {t('finance.common.delete')}
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
        <button type="button" className="rounded border px-2 py-1 text-sm" disabled={page <= 1} onClick={() => setPage((p) => p - 1)}>{t('common.pagination.prev')}</button>
        <span className="text-sm">{t('finance.common.page')} {page}</span>
        <button type="button" className="rounded border px-2 py-1 text-sm" disabled={(query.data?.items?.length ?? 0) < 10} onClick={() => setPage((p) => p + 1)}>{t('common.pagination.next')}</button>
      </div>
      <ConfirmDialog
        isOpen={deleteId !== null}
        title={t('finance.common.deleteConfirmTitle')}
        description={t('common.confirm')}
        confirmText={del.isPending ? t('finance.common.deleting') : t('finance.common.delete')}
        cancelText={t('common.cancel')}
        variant="danger"
        onClose={() => setDeleteId(null)}
        onConfirm={() => {
          if (deleteId === null) return
          del.mutate(deleteId, {
            onSuccess: () => {
              toast.success(t('finance.common.deleteSuccess'))
              setDeleteId(null)
            },
            onError: (err) => toast.error(getErrorMessage(err)),
          })
        }}
      />
    </div>
  )
}
