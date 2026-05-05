import { useState } from 'react'
import { Link } from 'react-router-dom'

import { OperationActionButtons } from '@/features/operation/components/OperationActionButtons'
import { OperationStatusBadge } from '@/features/operation/components/OperationStatusBadge'
import { useCommonAreaReservationsQuery } from '@/features/operation/hooks/useCommonAreaReservations'
import { useOperationLookups } from '@/features/operation/hooks/useOperationLookups'
import { EmptyState } from '@/shared/components/EmptyState'
import { PermissionDeniedNotice } from '@/shared/components/PermissionDeniedNotice'
import { useEffectiveCan } from '@/shared/hooks/useEffectiveCan'

export function CommonAreaReservationsPage() {
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
  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <h1 className="text-xl font-semibold">Reservations</h1>
        {canCreate ? (
          <Link to="/operations/common-area-reservations/new" className="rounded bg-violet-600 px-3 py-2 text-sm text-white">
            New
          </Link>
        ) : null}
      </div>
      <label className="text-sm">
        <span className="mb-1 block text-zinc-600 dark:text-zinc-300">Status</span>
        <select
          value={status}
          onChange={(event) => {
            setStatus(event.target.value)
            setPage(1)
          }}
          className="rounded border border-zinc-300 px-2 py-1.5 dark:border-zinc-700 dark:bg-zinc-900"
        >
          <option value="">All</option>
          <option value="pending">pending</option>
          <option value="approved">approved</option>
          <option value="rejected">rejected</option>
          <option value="cancelled">cancelled</option>
          <option value="completed">completed</option>
        </select>
      </label>
      <p className="text-sm text-zinc-500">Toplam kayit: {total}</p>
      {query.isLoading ? <div className="text-sm text-zinc-500">Yukleniyor...</div> : null}
      {query.isError ? <EmptyState title="Liste alinamadi" description="Lutfen tekrar deneyin." /> : null}
      {!query.isLoading && !query.isError && items.length === 0 ? (
        <EmptyState title="Reservation yok" description="Ilk kaydi olusturun." />
      ) : null}
      {!query.isLoading && !query.isError && items.length > 0 ? (
        <table className="min-w-full overflow-hidden rounded-xl border border-zinc-200 text-sm dark:border-zinc-800">
          <thead className="bg-zinc-100 dark:bg-zinc-900">
            <tr>
              <th className="px-3 py-2 text-left">ID</th>
              <th className="px-3 py-2 text-left">Common area</th>
              <th className="px-3 py-2 text-left">Resident</th>
              <th className="px-3 py-2 text-left">Start</th>
              <th className="px-3 py-2 text-left">Status</th>
              <th className="px-3 py-2 text-left">Action</th>
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
                  <div className="text-xs text-zinc-500">Unit: {row.unit_id ? (unitMap[row.unit_id] ?? `#${row.unit_id}`) : '-'}</div>
                  <div className="flex items-center gap-2">
                    {canView ? (
                      <Link to={`/operations/common-area-reservations/${row.id}`} className="text-violet-600">
                        Open
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
          Prev
        </button>
        <span className="text-sm">Page {page}</span>
        <button
          type="button"
          disabled={(query.data?.items?.length ?? 0) < 10}
          onClick={() => setPage((p) => p + 1)}
          className="rounded border px-2 py-1 text-sm"
        >
          Next
        </button>
      </div>
    </div>
  )
}
