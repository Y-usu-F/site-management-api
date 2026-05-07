import { FinanceStatCard } from '@/features/finance/components/FinanceStatCard'
import { useDashboardAnalyticsQuery } from '@/features/analytics/hooks/useDashboardAnalytics'
import { useTranslation } from 'react-i18next'

export function DashboardHomePage() {
  const { t } = useTranslation(['navigation', 'common', 'finance', 'operations', 'residents'])
  const analyticsQuery = useDashboardAnalyticsQuery()

  if (analyticsQuery.isLoading) {
    return <div className="text-sm text-zinc-500">{t('common.loading')}</div>
  }

  if (analyticsQuery.isError) {
    return <div className="text-sm text-red-600">{t('common.errorGeneric')}</div>
  }

  const analytics = analyticsQuery.data

  return (
    <div className="space-y-4">
      <div>
        <h1 className="text-xl font-semibold">{t('navigation.dashboard')}</h1>
        <p className="text-sm text-zinc-500">{t('navigation.analytics')}</p>
      </div>

      <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
        <FinanceStatCard
          to="/finance/due-items"
          title={t('finance.widgets.dashboardDueTotalTitle')}
          value={(analytics?.finance.due_total ?? 0).toFixed(2)}
          description={t('navigation.finance')}
        />
        <FinanceStatCard
          to="/finance/payments"
          title={t('finance.widgets.dashboardPaidTotalTitle')}
          value={(analytics?.finance.paid_total ?? 0).toFixed(2)}
          description={t('navigation.finance')}
        />
        <FinanceStatCard
          to="/finance/due-items"
          title={t('finance.widgets.dashboardUnpaidTotalTitle')}
          value={(analytics?.finance.unpaid_total ?? 0).toFixed(2)}
          description={t('navigation.finance')}
        />
        <FinanceStatCard
          to="/finance/payments"
          title={t('finance.widgets.dashboardPaymentCountTitle')}
          value={analytics?.finance.payment_count ?? 0}
          description={t('navigation.finance')}
        />
        <FinanceStatCard
          to="/operations/service-requests"
          title={t('operations.widgets.dashboardOpenServiceRequestsTitle')}
          value={analytics?.operations.open_service_requests ?? 0}
          description={t('navigation.operations')}
        />
        <FinanceStatCard
          to="/operations/work-orders"
          title={t('operations.widgets.dashboardActiveWorkOrdersTitle')}
          value={analytics?.operations.active_work_orders ?? 0}
          description={t('navigation.operations')}
        />
        <FinanceStatCard
          to="/operations/common-area-reservations"
          title={t('operations.widgets.dashboardUpcomingReservationsTitle')}
          value={analytics?.operations.upcoming_reservations ?? 0}
          description={t('navigation.operations')}
        />
        <FinanceStatCard
          to="/residents"
          title={t('residents.widgets.residentCountTitle')}
          value={analytics?.residents.resident_count ?? 0}
          description={t('navigation.residents')}
        />
        <FinanceStatCard
          to="/units"
          title={t('residents.widgets.unitCountTitle')}
          value={analytics?.residents.unit_count ?? 0}
          description={t('navigation.residents')}
        />
        <FinanceStatCard
          to="/units"
          title={t('residents.widgets.activeOccupancyCountTitle')}
          value={analytics?.residents.active_occupancy_count ?? 0}
          description={t('navigation.residents')}
        />
      </div>
    </div>
  )
}
