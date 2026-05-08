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
      <h1 className="text-xl font-semibold">{t('title', { ns: 'finance' })}</h1>
      <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
        <FinanceStatCard
          to="/finance/due-items"
          title={t('widgets.dueItemsTitle', { ns: 'finance' })}
          value={dueItems.data?.total ?? 0}
          description={t('widgets.dueItemsDescription', { ns: 'finance' })}
        />
        <FinanceStatCard
          to="/finance/payments"
          title={t('widgets.paymentsTitle', { ns: 'finance' })}
          value={payments.data?.total ?? 0}
          description={t('widgets.paymentsDescription', { ns: 'finance' })}
        />
        <FinanceStatCard
          to="/finance/deposits"
          title={t('widgets.depositsTitle', { ns: 'finance' })}
          value={deposits.data?.total ?? 0}
          description={t('widgets.depositsDescription', { ns: 'finance' })}
        />
        <FinanceStatCard
          to="/finance/due-periods"
          title={t('widgets.duePeriodsTitle', { ns: 'finance' })}
          value={duePeriods.data?.total ?? 0}
          description={t('widgets.duePeriodsDescription', { ns: 'finance' })}
        />
        <Link
          to="/finance/due-definitions"
          className="rounded-xl border border-zinc-200 bg-white p-4 transition hover:border-violet-300 dark:border-zinc-800 dark:bg-zinc-900"
        >
          <div className="text-base font-medium">{t('widgets.dueDefinitionsTitle', { ns: 'finance' })}</div>
          <div className="mt-1 text-sm text-zinc-500">{t('widgets.dueDefinitionsDescription', { ns: 'finance' })}</div>
        </Link>
      </div>
    </div>
  )
}
