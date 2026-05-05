import { Link } from 'react-router-dom'

import { FinanceStatCard } from '@/features/finance/components/FinanceStatCard'
import { useDepositsQuery } from '@/features/finance/hooks/useDepositsQuery'
import { useDueItemsQuery } from '@/features/finance/hooks/useDueItemsQuery'
import { useDuePeriodsQuery } from '@/features/finance/hooks/useDuePeriodsQuery'
import { usePaymentsQuery } from '@/features/finance/hooks/usePaymentsQuery'
import { useEffectiveCan } from '@/shared/hooks/useEffectiveCan'

export function FinanceDashboardPage() {
  const canDueItems = useEffectiveCan('due_item.list')
  const canPayments = useEffectiveCan('payment.list')
  const canDeposits = useEffectiveCan('deposit.list')
  const canDuePeriods = useEffectiveCan('due_period.list')

  const dueItems = useDueItemsQuery({ page: 1, per_page: 1 }, canDueItems)
  const payments = usePaymentsQuery({ page: 1, per_page: 1 }, canPayments)
  const deposits = useDepositsQuery({ page: 1, per_page: 1 }, canDeposits)
  const duePeriods = useDuePeriodsQuery({ page: 1, per_page: 1 }, canDuePeriods)

  return (
    <div className="space-y-4">
      <h1 className="text-xl font-semibold">Finance</h1>
      <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
        <FinanceStatCard
          to="/finance/due-items"
          title="Due Items"
          value={dueItems.data?.total ?? 0}
          description="Tahakkuk satirlari"
        />
        <FinanceStatCard
          to="/finance/payments"
          title="Payments"
          value={payments.data?.total ?? 0}
          description="Odeme kayitlari"
        />
        <FinanceStatCard
          to="/finance/deposits"
          title="Deposits"
          value={deposits.data?.total ?? 0}
          description="Depozito kayitlari"
        />
        <FinanceStatCard
          to="/finance/due-periods"
          title="Due Periods"
          value={duePeriods.data?.total ?? 0}
          description="Donem yonetimi"
        />
        <Link
          to="/finance/due-definitions"
          className="rounded-xl border border-zinc-200 bg-white p-4 transition hover:border-violet-300 dark:border-zinc-800 dark:bg-zinc-900"
        >
          <div className="text-base font-medium">Due definitions</div>
          <div className="mt-1 text-sm text-zinc-500">Aidat/borc tanimlari.</div>
        </Link>
      </div>
    </div>
  )
}
