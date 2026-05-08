import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { Link } from 'react-router-dom'

import { OperationActionButtons } from '@/features/operation/components/OperationActionButtons'
import { OperationStatusBadge } from '@/features/operation/components/OperationStatusBadge'
import { useWorkOrdersQuery } from '@/features/operation/hooks/useWorkOrders'
import { EmptyState } from '@/shared/components/EmptyState'
import { PermissionDeniedNotice } from '@/shared/components/PermissionDeniedNotice'
import { useEffectiveCan } from '@/shared/hooks/useEffectiveCan'

export function WorkOrdersPage() {
  const { t } = useTranslation(['operations', 'common'])
  const canList = useEffectiveCan('work_order.list')
  const canCreate = useEffectiveCan('work_order.create')
  const canView = useEffectiveCan('work_order.view')

  const [page, setPage] = useState(1)
  const [status, setStatus] = useState('')
  const query = useWorkOrdersQuery({ page, per_page: 10, status: status || undefined }, canList)
  if (!canList) return <PermissionDeniedNotice permission="work_order.list" />

  const items = query.data?.items ?? []
  const total = query.data?.total ?? 0
  const statusOptionLabel = (value: string) => {
    if (value === 'open') return t('operations.common.statusOpen')
    if (value === 'in_progress') return t('operations.common.statusInProgress')
    if (value === 'completed') return t('operations.common.statusCompleted')
    if (value === 'cancelled') return t('operations.common.statusCancelled')
    return value
  }
  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <h1 className="text-xl font-semibold">{t('operations.common.workOrders')}</h1>
        {canCreate ? (
          <Link to="/operations/work-orders/new" className="rounded bg-violet-600 px-3 py-2 text-sm text-white">
            {t('operations.common.new')}
          </Link>
        ) : null}
      </div>
      <label className="text-sm">
        <span className="mb-1 block text-zinc-600 dark:text-zinc-300">{t('operations.common.status')}</span>
        <select
          value={status}
          onChange={(event) => {
            setStatus(event.target.value)
            setPage(1)
          }}
          className="rounded border border-zinc-300 px-2 py-1.5 dark:border-zinc-700 dark:bg-zinc-900"
        >
          <option value="">{t('operations.common.all')}</option>
          <option value="open">{statusOptionLabel('open')}</option>
          <option value="in_progress">{statusOptionLabel('in_progress')}</option>
          <option value="completed">{statusOptionLabel('completed')}</option>
          <option value="cancelled">{statusOptionLabel('cancelled')}</option>
        </select>
      </label>
      <p className="text-sm text-zinc-500">{t('operations.common.totalRecords')}: {total}</p>
      {query.isLoading ? <div className="text-sm text-zinc-500">Yukleniyor...</div> : null}
      {query.isError ? <EmptyState title={t('operations.common.listFailed')} description={t('common.errorGeneric')} /> : null}
      {!query.isLoading && !query.isError && items.length === 0 ? (
        <EmptyState title={t('common.emptyTitle')} description={t('operations.common.createFirst')} />
      ) : null}
      {!query.isLoading && !query.isError && items.length > 0 ? (
        <table className="min-w-full overflow-hidden rounded-xl border border-zinc-200 text-sm dark:border-zinc-800">
          <thead className="bg-zinc-100 dark:bg-zinc-900">
            <tr>
              <th className="px-3 py-2 text-left">ID</th>
              <th className="px-3 py-2 text-left">{t('operations.common.serviceRequests')}</th>
              <th className="px-3 py-2 text-left">{t('operations.common.vendor')}</th>
              <th className="px-3 py-2 text-left">{t('operations.common.status')}</th>
              <th className="px-3 py-2 text-left">{t('operations.common.action')}</th>
            </tr>
          </thead>
          <tbody>
            {items.map((row) => (
              <tr key={row.id} className="border-t border-zinc-200 dark:border-zinc-800">
                <td className="px-3 py-2">{row.id}</td>
                <td className="px-3 py-2">{row.service_request_id}</td>
                <td className="px-3 py-2">{row.vendor_name ?? '-'}</td>
                <td className="px-3 py-2"><OperationStatusBadge status={row.status} /></td>
                <td className="px-3 py-2">
                  <div className="flex items-center gap-2">
                    {canView ? (
                      <Link className="text-violet-600" to={`/operations/work-orders/${row.id}`}>
                        {t('operations.common.open')}
                      </Link>
                    ) : null}
                    <OperationActionButtons entity="work_order" id={row.id} />
                  </div>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      ) : null}
      <div className="flex items-center gap-2">
        <button
          type="button"
          disabled={page <= 1}
          onClick={() => setPage((p) => p - 1)}
          className="rounded border px-2 py-1 text-sm"
        >
          {t('common.pagination.prev')}
        </button>
        <span className="text-sm">{t('operations.common.page')} {page}</span>
        <button
          type="button"
          disabled={(query.data?.items?.length ?? 0) < 10}
          onClick={() => setPage((p) => p + 1)}
          className="rounded border px-2 py-1 text-sm"
        >
          {t('common.pagination.next')}
        </button>
      </div>
    </div>
  )
}
