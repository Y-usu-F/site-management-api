import { useState } from 'react'
import { Link } from 'react-router-dom'
import { useTranslation } from 'react-i18next'

import { OperationActionButtons } from '@/features/operation/components/OperationActionButtons'
import { OperationStatusBadge } from '@/features/operation/components/OperationStatusBadge'
import { useCommonAreaReservationsQuery } from '@/features/operation/hooks/useCommonAreaReservations'
import { useOperationLookups } from '@/features/operation/hooks/useOperationLookups'
import { EmptyState } from '@/shared/components/EmptyState'
import { PermissionDeniedNotice } from '@/shared/components/PermissionDeniedNotice'
import { useEffectiveCan } from '@/shared/hooks/useEffectiveCan'

export function CommonAreaReservationsPage() {
  const { t } = useTranslation(['operations', 'common'])
  const canList = useEffectiveCan('common_area_reservation.list')
  const canCreate = useEffectiveCan('common_area_reservation.create')
  const canView = useEffectiveCan('common_area_reservation.view')
  const { commonAreaMap, residentMap, unitMap } = useOperationLookups()

  const [page, setPage] = useState(1)
  const [status, setStatus] = useState('')
  const query = useCommonAreaReservationsQuery({ page, per_page: 10, status: status || undefined }, canList)

  if (!canList) return <PermissionDeniedNotice permission="common_area_reservation.list" />
  const items = query.data?.items ?? []
  const total = query.data?.total ?? 0
  const statusOptionLabel = (value: string) => {
    if (value === 'pending') return t('statusOpen', { ns: 'operations' })
    if (value === 'approved') return t('actions.approve', { ns: 'operations' })
    if (value === 'rejected') return t('statusRejected', { ns: 'operations' })
    if (value === 'cancelled') return t('statusCancelled', { ns: 'operations' })
    if (value === 'completed') return t('statusCompleted', { ns: 'operations' })
    return value
  }
  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <h1 className="text-xl font-semibold">{t('reservations', { ns: 'operations' })}</h1>
        {canCreate ? (
          <Link to="/operations/common-area-reservations/new" className="rounded bg-violet-600 px-3 py-2 text-sm text-white">
            {t('new', { ns: 'operations' })}
          </Link>
        ) : null}
      </div>
      <label className="text-sm">
        <span className="mb-1 block text-zinc-600 dark:text-zinc-300">{t('status', { ns: 'operations' })}</span>
        <select
          value={status}
          onChange={(event) => {
            setStatus(event.target.value)
            setPage(1)
          }}
          className="rounded border border-zinc-300 px-2 py-1.5 dark:border-zinc-700 dark:bg-zinc-900"
        >
          <option value="">{t('all', { ns: 'operations' })}</option>
          <option value="pending">{statusOptionLabel('pending')}</option>
          <option value="approved">{statusOptionLabel('approved')}</option>
          <option value="rejected">{statusOptionLabel('rejected')}</option>
          <option value="cancelled">{statusOptionLabel('cancelled')}</option>
          <option value="completed">{statusOptionLabel('completed')}</option>
        </select>
      </label>
      <p className="text-sm text-zinc-500">Toplam kayit: {total}</p>
      {query.isLoading ? <div className="text-sm text-zinc-500">{t('loading', { ns: 'operations' })}</div> : null}
      {query.isError ? <EmptyState title={t('listFailed', { ns: 'operations' })} description={t('errorGeneric', { ns: 'common' })} /> : null}
      {!query.isLoading && !query.isError && items.length === 0 ? (
        <EmptyState title={t('noData', { ns: 'operations' })} description={t('createFirst', { ns: 'operations' })} />
      ) : null}
      {!query.isLoading && !query.isError && items.length > 0 ? (
        <table className="min-w-full overflow-hidden rounded-xl border border-zinc-200 text-sm dark:border-zinc-800">
          <thead className="bg-zinc-100 dark:bg-zinc-900">
            <tr>
              <th className="px-3 py-2 text-left">ID</th>
              <th className="px-3 py-2 text-left">{t('commonAreas', { ns: 'operations' })}</th>
              <th className="px-3 py-2 text-left">{t('resident', { ns: 'operations' })}</th>
              <th className="px-3 py-2 text-left">{t('start', { ns: 'operations' })}</th>
              <th className="px-3 py-2 text-left">{t('status', { ns: 'operations' })}</th>
              <th className="px-3 py-2 text-left">{t('action', { ns: 'operations' })}</th>
            </tr>
          </thead>
          <tbody>
            {items.map((row) => (
              <tr key={row.id} className="border-t border-zinc-200 dark:border-zinc-800">
                <td className="px-3 py-2">{row.id}</td>
                <td className="px-3 py-2">{commonAreaMap[row.common_area_id] ?? `#${row.common_area_id}`}</td>
                <td className="px-3 py-2">{row.resident_profile_id ? (residentMap[row.resident_profile_id] ?? `#${row.resident_profile_id}`) : '-'}</td>
                <td className="px-3 py-2">{row.start_at}</td>
                <td className="px-3 py-2"><OperationStatusBadge status={row.status} /></td>
                <td className="px-3 py-2">
                  <div className="text-xs text-zinc-500">{t('unitLabel', { ns: 'operations' })}: {row.unit_id ? (unitMap[row.unit_id] ?? `#${row.unit_id}`) : '-'}</div>
                  <div className="flex items-center gap-2">
                    {canView ? (
                      <Link to={`/operations/common-area-reservations/${row.id}`} className="text-violet-600">
                        {t('open', { ns: 'operations' })}
                      </Link>
                    ) : null}
                    <OperationActionButtons entity="common_area_reservation" id={row.id} />
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
          {t('pagination.prev', { ns: 'common' })}
        </button>
        <span className="text-sm">{t('page', { ns: 'operations' })} {page}</span>
        <button
          type="button"
          disabled={(query.data?.items?.length ?? 0) < 10}
          onClick={() => setPage((p) => p + 1)}
          className="rounded border px-2 py-1 text-sm"
        >
          {t('pagination.next', { ns: 'common' })}
        </button>
      </div>
    </div>
  )
}
