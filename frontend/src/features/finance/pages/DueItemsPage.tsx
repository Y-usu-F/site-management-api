import { useState } from 'react'
import { Link } from 'react-router-dom'

import { FinanceStatusBadge } from '@/features/finance/components/FinanceStatusBadge'
import { MoneyText } from '@/features/finance/components/MoneyText'
import { useDueDefinitionsQuery } from '@/features/finance/hooks/useDueDefinitionsQuery'
import { useDueItemsQuery } from '@/features/finance/hooks/useDueItemsQuery'
import { useDuePeriodsQuery } from '@/features/finance/hooks/useDuePeriodsQuery'
import { EmptyState } from '@/shared/components/EmptyState'
import { PermissionDeniedNotice } from '@/shared/components/PermissionDeniedNotice'
import { useEffectiveCan } from '@/shared/hooks/useEffectiveCan'

export function DueItemsPage() {
  const canList = useEffectiveCan('due_item.list')
  const [page, setPage] = useState(1)
  const [status, setStatus] = useState('')
  const [unitId, setUnitId] = useState('')
  const [duePeriodId, setDuePeriodId] = useState('')

  const query = useDueItemsQuery(
    {
      page,
      per_page: 10,
      status: status || undefined,
      unit_id: unitId ? Number(unitId) : undefined,
      due_period_id: duePeriodId ? Number(duePeriodId) : undefined,
    },
    canList,
  )
  const duePeriods = useDuePeriodsQuery({ page: 1, per_page: 100 }, canList)
  const dueDefinitions = useDueDefinitionsQuery({ page: 1, per_page: 100 }, canList)

  if (!canList) return <PermissionDeniedNotice permission="due_item.list" />

  const definitionMap = new Map((dueDefinitions.data?.items ?? []).map((item) => [item.id, item.name]))
  const items = query.data?.items ?? []
  return (
    <div className="space-y-4">
      <h1 className="text-xl font-semibold">Due items</h1>
      <div className="grid gap-3 rounded-xl border border-zinc-200 p-3 sm:grid-cols-4 dark:border-zinc-800">
        <select
          value={status}
          onChange={(e) => {
            setStatus(e.target.value)
            setPage(1)
          }}
          className="rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-950"
        >
          <option value="">Status (all)</option>
          <option value="unpaid">unpaid</option>
          <option value="partial">partial</option>
          <option value="paid">paid</option>
          <option value="cancelled">cancelled</option>
        </select>
        <select
          value={duePeriodId}
          onChange={(e) => {
            setDuePeriodId(e.target.value)
            setPage(1)
          }}
          className="rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-950"
        >
          <option value="">Due period (all)</option>
          {(duePeriods.data?.items ?? []).map((period) => (
            <option key={period.id} value={period.id}>
              #{period.id} - {period.period_key}
            </option>
          ))}
        </select>
        <input
          value={unitId}
          onChange={(e) => {
            setUnitId(e.target.value)
            setPage(1)
          }}
          placeholder="Unit id"
          className="rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-950"
        />
        <button
          type="button"
          className="rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-600"
          onClick={() => {
            setStatus('')
            setDuePeriodId('')
            setUnitId('')
            setPage(1)
          }}
        >
          Clear filters
        </button>
      </div>
      <p className="text-xs text-zinc-500">
        Not: `due_definition_id` filtresi backend list endpointinde desteklenmedigi icin eklenmedi.
      </p>
      {items.length === 0 ? (
        <EmptyState title="Due item yok" description="Uygun donemlerde due batch ile olusur." />
      ) : (
        <div className="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-800">
          <table className="min-w-full text-sm">
            <thead className="bg-zinc-100 dark:bg-zinc-900">
              <tr>
                <th className="px-3 py-2 text-left">ID</th>
                <th className="px-3 py-2 text-left">Unit</th>
                <th className="px-3 py-2 text-left">Due definition</th>
                <th className="px-3 py-2 text-left">Amount</th>
                <th className="px-3 py-2 text-left">Paid</th>
                <th className="px-3 py-2 text-left">Remaining</th>
                <th className="px-3 py-2 text-left">Status</th>
                <th className="px-3 py-2 text-left">Due date</th>
                <th className="px-3 py-2 text-left">Action</th>
              </tr>
            </thead>
            <tbody>
              {items.map((row) => (
                <tr key={row.id} className="border-t border-zinc-200 dark:border-zinc-800">
                  <td className="px-3 py-2">{row.id}</td>
                  <td className="px-3 py-2">{row.unit_id}</td>
                  <td className="px-3 py-2">
                    #{row.due_definition_id} - {definitionMap.get(row.due_definition_id) ?? '-'}
                  </td>
                  <td className="px-3 py-2"><MoneyText amount={row.amount} currency={row.currency} /></td>
                  <td className="px-3 py-2"><MoneyText amount={row.paid_amount} currency={row.currency} /></td>
                  <td className="px-3 py-2"><MoneyText amount={row.remaining_amount} currency={row.currency} /></td>
                  <td className="px-3 py-2"><FinanceStatusBadge status={row.status} /></td>
                  <td className="px-3 py-2">{row.due_date}</td>
                  <td className="px-3 py-2">
                    <Link to={`/finance/due-items/${row.id}`} className="text-violet-600">
                      Open
                    </Link>
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
    </div>
  )
}
