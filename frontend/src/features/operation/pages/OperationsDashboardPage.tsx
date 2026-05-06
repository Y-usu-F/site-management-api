import { Link } from 'react-router-dom'
import { useTranslation } from 'react-i18next'

import { FinanceStatCard } from '@/features/finance/components/FinanceStatCard'
import { useOperationsSummaryQuery } from '@/features/operation/hooks/useOperationsSummary'
import { useEffectiveCan } from '@/shared/hooks/useEffectiveCan'

const cards = [
  { to: '/operations/service-requests', titleKey: 'operations.cards.serviceRequests' },
  { to: '/operations/work-orders', titleKey: 'operations.cards.workOrders' },
  { to: '/operations/common-areas', titleKey: 'operations.cards.commonAreas' },
  { to: '/operations/common-area-reservations', titleKey: 'operations.cards.reservations' },
  { to: '/operations/assets', titleKey: 'operations.cards.assets' },
  { to: '/operations/asset-maintenance-plans', titleKey: 'operations.cards.maintenancePlans' },
  { to: '/operations/asset-maintenance-records', titleKey: 'operations.cards.maintenanceRecords' },
]

export function OperationsDashboardPage() {
  const { t } = useTranslation(['operations'])
  const canServiceRequests = useEffectiveCan('service_request.list')
  const summaryQuery = useOperationsSummaryQuery(canServiceRequests)

  const summary = summaryQuery.data
  const upcomingReservationsCount =
    (summary?.reservations.pending ?? 0) + (summary?.reservations.approved ?? 0)
  const summaryDescription = !canServiceRequests
    ? t('operations.summary.permissionRequired')
    : summaryQuery.isLoading
      ? t('operations.summary.loading')
      : summaryQuery.isError
        ? t('operations.summary.failed')
        : ''

  return (
    <div className="space-y-4">
      <h1 className="text-xl font-semibold">{t('operations.title')}</h1>
      <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
        <FinanceStatCard
          to="/operations/service-requests"
          title={t('operations.widgets.openServiceRequestsTitle')}
          value={summary?.service_requests.open ?? 0}
          description={summaryDescription || t('operations.widgets.openServiceRequestsDescription')}
        />
        <FinanceStatCard
          to="/operations/work-orders"
          title={t('operations.widgets.activeWorkOrdersTitle')}
          value={summary?.work_orders.in_progress ?? 0}
          description={summaryDescription || t('operations.widgets.activeWorkOrdersDescription')}
        />
        <FinanceStatCard
          to="/operations/common-area-reservations"
          title={t('operations.widgets.upcomingReservationsTitle')}
          value={upcomingReservationsCount}
          description={summaryDescription || t('operations.widgets.upcomingReservationsDescription')}
        />
        <FinanceStatCard
          to="/operations/asset-maintenance-plans"
          title={t('operations.widgets.activeMaintenancePlansTitle')}
          value={summary?.maintenance.active_plans ?? 0}
          description={summaryDescription || t('operations.widgets.activeMaintenancePlansDescription')}
        />
      </div>
      <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
        {cards.map((card) => (
          <Link key={card.to} to={card.to} className="rounded-xl border border-zinc-200 bg-white p-4 hover:border-violet-300 dark:border-zinc-800 dark:bg-zinc-900">
            <div className="text-base font-medium">{t(card.titleKey)}</div>
          </Link>
        ))}
      </div>
    </div>
  )
}
