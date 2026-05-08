import { FinanceStatCard } from '@/features/finance/components/FinanceStatCard'
import { useDashboardAnalyticsQuery } from '@/features/analytics/hooks/useDashboardAnalytics'
import { AnalyticsCharts } from '@/features/dashboard/components/AnalyticsCharts'
import type { AnalyticsRange } from '@/features/analytics/api/dashboardAnalyticsApi'
import { useState } from 'react'
import { useTranslation } from 'react-i18next'

export function DashboardHomePage() {
  const { t } = useTranslation(['navigation', 'common', 'finance', 'operations', 'residents', 'analytics'])
  const [range, setRange] = useState<AnalyticsRange>('30d')
  const analyticsQuery = useDashboardAnalyticsQuery(range)

  if (analyticsQuery.isLoading) {
    return <div className="text-sm text-zinc-500">{t('loading', { ns: 'common' })}</div>
  }

  if (analyticsQuery.isError) {
    return <div className="text-sm text-red-600">{t('errorGeneric', { ns: 'common' })}</div>
  }

  const analytics = analyticsQuery.data

  return (
    <div className="space-y-4">
      <div>
        <h1 className="text-xl font-semibold">{t('dashboard', { ns: 'navigation' })}</h1>
        <p className="text-sm text-zinc-500">{t('analytics', { ns: 'navigation' })}</p>
      </div>

      <div className="inline-flex rounded-lg border border-zinc-300 p-1 dark:border-zinc-700">
        {(['7d', '30d', '90d'] as const).map((value) => (
          <button
            key={value}
            type="button"
            onClick={() => setRange(value)}
            className={[
              'rounded-md px-3 py-1.5 text-xs font-medium transition',
              range === value
                ? 'bg-violet-600 text-white'
                : 'text-zinc-600 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800',
            ].join(' ')}
          >
            {value === '7d'
              ? t('range.last7Days', { ns: 'analytics' })
              : value === '30d'
                ? t('range.last30Days', { ns: 'analytics' })
                : t('range.last90Days', { ns: 'analytics' })}
          </button>
        ))}
      </div>

      <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
        <FinanceStatCard
          to="/finance/due-items"
          title={t('widgets.dashboardDueTotalTitle', { ns: 'finance' })}
          value={(analytics?.finance.due_total ?? 0).toFixed(2)}
          description={t('finance', { ns: 'navigation' })}
        />
        <FinanceStatCard
          to="/finance/payments"
          title={t('widgets.dashboardPaidTotalTitle', { ns: 'finance' })}
          value={(analytics?.finance.paid_total ?? 0).toFixed(2)}
          description={t('finance', { ns: 'navigation' })}
        />
        <FinanceStatCard
          to="/finance/due-items"
          title={t('widgets.dashboardUnpaidTotalTitle', { ns: 'finance' })}
          value={(analytics?.finance.unpaid_total ?? 0).toFixed(2)}
          description={t('finance', { ns: 'navigation' })}
        />
        <FinanceStatCard
          to="/finance/payments"
          title={t('widgets.dashboardPaymentCountTitle', { ns: 'finance' })}
          value={analytics?.finance.payment_count ?? 0}
          description={t('finance', { ns: 'navigation' })}
        />
        <FinanceStatCard
          to="/operations/service-requests"
          title={t('widgets.dashboardOpenServiceRequestsTitle', { ns: 'operations' })}
          value={analytics?.operations.open_service_requests ?? 0}
          description={t('operations', { ns: 'navigation' })}
        />
        <FinanceStatCard
          to="/operations/work-orders"
          title={t('widgets.dashboardActiveWorkOrdersTitle', { ns: 'operations' })}
          value={analytics?.operations.active_work_orders ?? 0}
          description={t('operations', { ns: 'navigation' })}
        />
        <FinanceStatCard
          to="/operations/common-area-reservations"
          title={t('widgets.dashboardUpcomingReservationsTitle', { ns: 'operations' })}
          value={analytics?.operations.upcoming_reservations ?? 0}
          description={t('operations', { ns: 'navigation' })}
        />
        <FinanceStatCard
          to="/residents"
          title={t('widgets.residentCountTitle', { ns: 'residents' })}
          value={analytics?.residents.resident_count ?? 0}
          description={t('residents', { ns: 'navigation' })}
        />
        <FinanceStatCard
          to="/units"
          title={t('widgets.unitCountTitle', { ns: 'residents' })}
          value={analytics?.residents.unit_count ?? 0}
          description={t('residents', { ns: 'navigation' })}
        />
        <FinanceStatCard
          to="/units"
          title={t('widgets.activeOccupancyCountTitle', { ns: 'residents' })}
          value={analytics?.residents.active_occupancy_count ?? 0}
          description={t('residents', { ns: 'navigation' })}
        />
      </div>

      {analytics ? (
        <AnalyticsCharts
          analytics={analytics}
          labels={{
            paymentsTrend: t('trends.payments', { ns: 'analytics' }),
            serviceRequestsTrend: t('trends.serviceRequests', { ns: 'analytics' }),
            serviceRequestDistribution: t('distributions.serviceRequests', { ns: 'analytics' }),
            workOrderDistribution: t('distributions.workOrders', { ns: 'analytics' }),
            totalAxis: t('axes.total', { ns: 'analytics' }),
            countAxis: t('axes.count', { ns: 'analytics' }),
          }}
        />
      ) : null}
    </div>
  )
}
