import { Link } from 'react-router-dom'
import { useTranslation } from 'react-i18next'

import { FinanceStatCard } from '@/features/finance/components/FinanceStatCard'
import { useDepositsQuery } from '@/features/finance/hooks/useDepositsQuery'
import { useDueItemsQuery } from '@/features/finance/hooks/useDueItemsQuery'
import { useDuePeriodsQuery } from '@/features/finance/hooks/useDuePeriodsQuery'
import { usePaymentsQuery } from '@/features/finance/hooks/usePaymentsQuery'
import { useEffectiveCan } from '@/shared/hooks/useEffectiveCan'

export function FinanceDashboardPage() {
  const { t } = useTranslation(['finance'])
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
      <h1 className="text-xl font-semibold">{t('finance.title')}</h1>
      <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
        <FinanceStatCard
          to="/finance/due-items"
          title={t('finance.widgets.dueItemsTitle')}
          value={dueItems.data?.total ?? 0}
          description={t('finance.widgets.dueItemsDescription')}
        />
        <FinanceStatCard
          to="/finance/payments"
          title={t('finance.widgets.paymentsTitle')}
          value={payments.data?.total ?? 0}
          description={t('finance.widgets.paymentsDescription')}
        />
        <FinanceStatCard
          to="/finance/deposits"
          title={t('finance.widgets.depositsTitle')}
          value={deposits.data?.total ?? 0}
          description={t('finance.widgets.depositsDescription')}
        />
        <FinanceStatCard
          to="/finance/due-periods"
          title={t('finance.widgets.duePeriodsTitle')}
          value={duePeriods.data?.total ?? 0}
          description={t('finance.widgets.duePeriodsDescription')}
        />
        <Link
          to="/finance/due-definitions"
          className="rounded-xl border border-zinc-200 bg-white p-4 transition hover:border-violet-300 dark:border-zinc-800 dark:bg-zinc-900"
        >
          <div className="text-base font-medium">{t('finance.widgets.dueDefinitionsTitle')}</div>
          <div className="mt-1 text-sm text-zinc-500">{t('finance.widgets.dueDefinitionsDescription')}</div>
        </Link>
      </div>
    </div>
  )
}
