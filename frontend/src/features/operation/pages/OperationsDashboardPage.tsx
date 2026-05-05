import { Link } from 'react-router-dom'

import { FinanceStatCard } from '@/features/finance/components/FinanceStatCard'
import { useCommonAreaReservationsQuery } from '@/features/operation/hooks/useCommonAreaReservations'
import { useServiceRequestsQuery } from '@/features/operation/hooks/useServiceRequests'
import { useWorkOrdersQuery } from '@/features/operation/hooks/useWorkOrders'
import { useAssetMaintenancePlansQuery } from '@/features/operation/hooks/useAssetMaintenancePlans'
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
  const canWorkOrders = useEffectiveCan('work_order.list')
  const canReservations = useEffectiveCan('common_area_reservation.list')
  const canMaintenancePlans = useEffectiveCan('asset_maintenance_plan.list')

  // No dedicated summary endpoints in backend routes; aggregate via list totals.
  const openServiceRequests = useServiceRequestsQuery(
    { page: 1, per_page: 1, status: 'open' },
    canServiceRequests,
  )
  const activeWorkOrders = useWorkOrdersQuery(
    { page: 1, per_page: 1, status: 'in_progress' },
    canWorkOrders,
  )
  const upcomingReservationsPending = useCommonAreaReservationsQuery(
    { page: 1, per_page: 1, status: 'pending' },
    canReservations,
  )
  const upcomingReservationsApproved = useCommonAreaReservationsQuery(
    { page: 1, per_page: 1, status: 'approved' },
    canReservations,
  )
  const maintenancePlansActive = useAssetMaintenancePlansQuery(
    { page: 1, per_page: 1, status: 'active' },
    canMaintenancePlans,
  )

  const upcomingReservationsCount =
    (upcomingReservationsPending.data?.total ?? 0) + (upcomingReservationsApproved.data?.total ?? 0)

  return (
    <div className="space-y-4">
      <h1 className="text-xl font-semibold">Operations</h1>
      <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
        <FinanceStatCard
          to="/operations/service-requests"
          title="Open Service Requests"
          value={openServiceRequests.data?.total ?? 0}
          description={openServiceRequests.isLoading ? 'Yukleniyor...' : 'Acil/aktif is talepleri'}
        />
        <FinanceStatCard
          to="/operations/work-orders"
          title="Active Work Orders"
          value={activeWorkOrders.data?.total ?? 0}
          description={activeWorkOrders.isLoading ? 'Yukleniyor...' : 'Devam eden is emirleri'}
        />
        <FinanceStatCard
          to="/operations/common-area-reservations"
          title="Upcoming Reservations"
          value={upcomingReservationsCount}
          description={
            upcomingReservationsPending.isLoading || upcomingReservationsApproved.isLoading
              ? 'Yukleniyor...'
              : 'Pending + approved rezervasyonlar'
          }
        />
        <FinanceStatCard
          to="/operations/asset-maintenance-plans"
          title="Overdue Maintenance"
          value={maintenancePlansActive.data?.total ?? 0}
          description={
            maintenancePlansActive.isLoading
              ? 'Yukleniyor...'
              : 'Backend overdue filtresi olmadigi icin active plan sayisi gosterilir'
          }
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
