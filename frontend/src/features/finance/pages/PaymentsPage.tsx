import { useState } from 'react'
import { Link } from 'react-router-dom'

import { FinanceStatusBadge } from '@/features/finance/components/FinanceStatusBadge'
import { MoneyText } from '@/features/finance/components/MoneyText'
import { usePaymentsQuery } from '@/features/finance/hooks/usePaymentsQuery'
import { EmptyState } from '@/shared/components/EmptyState'
import { PermissionDeniedNotice } from '@/shared/components/PermissionDeniedNotice'
import { useEffectiveCan } from '@/shared/hooks/useEffectiveCan'
import { formatPaymentMethod } from '@/features/finance/utils/financeFormat'

export function PaymentsPage() {
  const canList = useEffectiveCan('payment.list')
  const canCreate = useEffectiveCan('payment.create_manual')
  const [page, setPage] = useState(1)
  const query = usePaymentsQuery({ page, per_page: 10 }, canList)

  if (!canList) return <PermissionDeniedNotice permission="payment.list" />
  const items = query.data?.items ?? []
  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <h1 className="text-xl font-semibold">Payments</h1>
        {canCreate ? (
          <Link to="/finance/payments/new" className="rounded-lg bg-violet-600 px-3 py-2 text-sm text-white">
            New payment
          </Link>
        ) : null}
      </div>
      {items.length === 0 ? (
        <EmptyState title="Payment yok" description="Manual payment olusturabilirsiniz." />
      ) : (
        <div className="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-800">
          <table className="min-w-full text-sm">
            <thead className="bg-zinc-100 dark:bg-zinc-900">
              <tr>
                <th className="px-3 py-2 text-left">ID</th>
                <th className="px-3 py-2 text-left">No</th>
                <th className="px-3 py-2 text-left">Resident</th>
                <th className="px-3 py-2 text-left">Unit</th>
                <th className="px-3 py-2 text-left">Amount</th>
                <th className="px-3 py-2 text-left">Method</th>
                <th className="px-3 py-2 text-left">Status</th>
                <th className="px-3 py-2 text-left">Date</th>
                <th className="px-3 py-2 text-left">Action</th>
              </tr>
            </thead>
            <tbody>
              {items.map((row) => (
                <tr key={row.id} className="border-t border-zinc-200 dark:border-zinc-800">
                  <td className="px-3 py-2">{row.id}</td>
                  <td className="px-3 py-2">{row.payment_no}</td>
                  <td className="px-3 py-2">{row.resident_profile_id ?? '-'}</td>
                  <td className="px-3 py-2">{row.unit_id ?? '-'}</td>
                  <td className="px-3 py-2"><MoneyText amount={row.amount} currency={row.currency} /></td>
                  <td className="px-3 py-2">{formatPaymentMethod(row.method)}</td>
                  <td className="px-3 py-2"><FinanceStatusBadge status={row.status} /></td>
                  <td className="px-3 py-2">{row.payment_date}</td>
                  <td className="px-3 py-2">
                    <Link to={`/finance/payments/${row.id}`} className="text-violet-600">
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
