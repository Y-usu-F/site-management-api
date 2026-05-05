import { useState } from 'react'
import { Link } from 'react-router-dom'

import { OperationActionButtons } from '@/features/operation/components/OperationActionButtons'
import { useWorkOrdersQuery } from '@/features/operation/hooks/useWorkOrders'
import { EmptyState } from '@/shared/components/EmptyState'
import { PermissionDeniedNotice } from '@/shared/components/PermissionDeniedNotice'
import { useEffectiveCan } from '@/shared/hooks/useEffectiveCan'

export function WorkOrdersPage() {
  const canList = useEffectiveCan('work_order.list')
  const canCreate = useEffectiveCan('work_order.create')
  const canView = useEffectiveCan('work_order.view')

  const [page, setPage] = useState(1)
  const [status, setStatus] = useState('')
  const query = useWorkOrdersQuery({ page, per_page: 10, status: status || undefined }, canList)
  if (!canList) return <PermissionDeniedNotice permission="work_order.list" />

  const items = query.data?.items ?? []
  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <h1 className="text-xl font-semibold">Work orders</h1>
        {canCreate ? (
          <Link to="/operations/work-orders/new" className="rounded bg-violet-600 px-3 py-2 text-sm text-white">
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
          <option value="open">open</option>
          <option value="in_progress">in_progress</option>
          <option value="completed">completed</option>
          <option value="cancelled">cancelled</option>
        </select>
      </label>
      {items.length === 0 ? (
        <EmptyState title="Work order yok" description="Ilk kaydi olusturun." />
      ) : (
        <table className="min-w-full overflow-hidden rounded-xl border border-zinc-200 text-sm dark:border-zinc-800">
          <thead className="bg-zinc-100 dark:bg-zinc-900">
            <tr>
              <th className="px-3 py-2 text-left">ID</th>
              <th className="px-3 py-2 text-left">Service request</th>
              <th className="px-3 py-2 text-left">Vendor</th>
              <th className="px-3 py-2 text-left">Status</th>
              <th className="px-3 py-2 text-left">Action</th>
            </tr>
          </thead>
          <tbody>
            {items.map((row) => (
              <tr key={row.id} className="border-t border-zinc-200 dark:border-zinc-800">
                <td className="px-3 py-2">{row.id}</td>
                <td className="px-3 py-2">{row.service_request_id}</td>
                <td className="px-3 py-2">{row.vendor_name ?? '-'}</td>
                <td className="px-3 py-2">{row.status ?? '-'}</td>
                <td className="px-3 py-2">
                  <div className="flex items-center gap-2">
                    {canView ? (
                      <Link className="text-violet-600" to={`/operations/work-orders/${row.id}`}>
                        Open
                      </Link>
                    ) : null}
                    <OperationActionButtons entity="work_order" id={row.id} />
                  </div>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      )}
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
