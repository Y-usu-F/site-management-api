import { Link } from 'react-router-dom'

import { FinanceStatCard } from '@/features/finance/components/FinanceStatCard'
import { useOperationsSummaryQuery } from '@/features/operation/hooks/useOperationsSummary'
import { useEffectiveCan } from '@/shared/hooks/useEffectiveCan'

const cards = [
  { to: '/operations/service-requests', title: 'Service Requests' },
  { to: '/operations/work-orders', title: 'Work Orders' },
  { to: '/operations/common-areas', title: 'Common Areas' },
  { to: '/operations/common-area-reservations', title: 'Reservations' },
  { to: '/operations/assets', title: 'Assets' },
  { to: '/operations/asset-maintenance-plans', title: 'Maintenance Plans' },
  { to: '/operations/asset-maintenance-records', title: 'Maintenance Records' },
]

export function OperationsDashboardPage() {
  const canServiceRequests = useEffectiveCan('service_request.list')
  const summaryQuery = useOperationsSummaryQuery(canServiceRequests)

  const summary = summaryQuery.data
  const upcomingReservationsCount =
    (summary?.reservations.pending ?? 0) + (summary?.reservations.approved ?? 0)
  const summaryDescription = !canServiceRequests
    ? 'Ozet goruntulemek icin service_request.list yetkisi gerekli'
    : summaryQuery.isLoading
      ? 'Yukleniyor...'
      : summaryQuery.isError
        ? 'Ozet alinamadi'
        : ''

  return (
    <div className="space-y-4">
      <h1 className="text-xl font-semibold">Operations</h1>
      <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
        <FinanceStatCard
          to="/operations/service-requests"
          title="Open Service Requests"
          value={summary?.service_requests.open ?? 0}
          description={summaryDescription || 'Acil/aktif is talepleri'}
        />
        <FinanceStatCard
          to="/operations/work-orders"
          title="Active Work Orders"
          value={summary?.work_orders.in_progress ?? 0}
          description={summaryDescription || 'Devam eden is emirleri'}
        />
        <FinanceStatCard
          to="/operations/common-area-reservations"
          title="Upcoming Reservations"
          value={upcomingReservationsCount}
          description={summaryDescription || 'Pending + approved rezervasyonlar'}
        />
        <FinanceStatCard
          to="/operations/asset-maintenance-plans"
          title="Active Maintenance Plans"
          value={summary?.maintenance.active_plans ?? 0}
          description={summaryDescription || 'Currently active maintenance plans'}
        />
      </div>
      <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
        {cards.map((card) => (
          <Link key={card.to} to={card.to} className="rounded-xl border border-zinc-200 bg-white p-4 hover:border-violet-300 dark:border-zinc-800 dark:bg-zinc-900">
            <div className="text-base font-medium">{card.title}</div>
          </Link>
        ))}
      </div>
    </div>
  )
}
