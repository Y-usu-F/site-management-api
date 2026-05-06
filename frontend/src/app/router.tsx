import { Suspense, lazy, type ReactNode } from 'react'
import { Navigate, Route, Routes } from 'react-router-dom'

import { LoginPage } from '@/features/auth/LoginPage'
import { ProtectedRoute } from '@/features/auth/ProtectedRoute'
const AnnouncementsPage = lazy(() =>
  import('@/features/communication/AnnouncementsPage').then((m) => ({ default: m.AnnouncementsPage })),
)
const NotificationsPage = lazy(() =>
  import('@/features/communication/NotificationsPage').then((m) => ({ default: m.NotificationsPage })),
)
const DashboardHomePage = lazy(() =>
  import('@/features/dashboard/DashboardHomePage').then((m) => ({ default: m.DashboardHomePage })),
)
const DepositDetailPage = lazy(() =>
  import('@/features/finance/pages/DepositDetailPage').then((m) => ({ default: m.DepositDetailPage })),
)
const DepositFormPage = lazy(() =>
  import('@/features/finance/pages/DepositFormPage').then((m) => ({ default: m.DepositFormPage })),
)
const DepositsPage = lazy(() =>
  import('@/features/finance/pages/DepositsPage').then((m) => ({ default: m.DepositsPage })),
)
const DueDefinitionDetailPage = lazy(() =>
  import('@/features/finance/pages/DueDefinitionDetailPage').then((m) => ({ default: m.DueDefinitionDetailPage })),
)
const DueDefinitionFormPage = lazy(() =>
  import('@/features/finance/pages/DueDefinitionFormPage').then((m) => ({ default: m.DueDefinitionFormPage })),
)
const DueDefinitionsPage = lazy(() =>
  import('@/features/finance/pages/DueDefinitionsPage').then((m) => ({ default: m.DueDefinitionsPage })),
)
const DueItemDetailPage = lazy(() =>
  import('@/features/finance/pages/DueItemDetailPage').then((m) => ({ default: m.DueItemDetailPage })),
)
const DueItemsPage = lazy(() =>
  import('@/features/finance/pages/DueItemsPage').then((m) => ({ default: m.DueItemsPage })),
)
const DuePeriodDetailPage = lazy(() =>
  import('@/features/finance/pages/DuePeriodDetailPage').then((m) => ({ default: m.DuePeriodDetailPage })),
)
const DuePeriodFormPage = lazy(() =>
  import('@/features/finance/pages/DuePeriodFormPage').then((m) => ({ default: m.DuePeriodFormPage })),
)
const DuePeriodsPage = lazy(() =>
  import('@/features/finance/pages/DuePeriodsPage').then((m) => ({ default: m.DuePeriodsPage })),
)
const FinanceDashboardPage = lazy(() =>
  import('@/features/finance/pages/FinanceDashboardPage').then((m) => ({ default: m.FinanceDashboardPage })),
)
const PaymentDetailPage = lazy(() =>
  import('@/features/finance/pages/PaymentDetailPage').then((m) => ({ default: m.PaymentDetailPage })),
)
const PaymentFormPage = lazy(() =>
  import('@/features/finance/pages/PaymentFormPage').then((m) => ({ default: m.PaymentFormPage })),
)
const PaymentsPage = lazy(() =>
  import('@/features/finance/pages/PaymentsPage').then((m) => ({ default: m.PaymentsPage })),
)
const AssetDetailPage = lazy(() =>
  import('@/features/operation/pages/AssetDetailPage').then((m) => ({ default: m.AssetDetailPage })),
)
const AssetFormPage = lazy(() =>
  import('@/features/operation/pages/AssetFormPage').then((m) => ({ default: m.AssetFormPage })),
)
const AssetMaintenancePlanDetailPage = lazy(() =>
  import('@/features/operation/pages/AssetMaintenancePlanDetailPage').then((m) => ({ default: m.AssetMaintenancePlanDetailPage })),
)
const AssetMaintenancePlanFormPage = lazy(() =>
  import('@/features/operation/pages/AssetMaintenancePlanFormPage').then((m) => ({ default: m.AssetMaintenancePlanFormPage })),
)
const AssetMaintenancePlansPage = lazy(() =>
  import('@/features/operation/pages/AssetMaintenancePlansPage').then((m) => ({ default: m.AssetMaintenancePlansPage })),
)
const AssetMaintenanceRecordDetailPage = lazy(() =>
  import('@/features/operation/pages/AssetMaintenanceRecordDetailPage').then((m) => ({ default: m.AssetMaintenanceRecordDetailPage })),
)
const AssetMaintenanceRecordFormPage = lazy(() =>
  import('@/features/operation/pages/AssetMaintenanceRecordFormPage').then((m) => ({ default: m.AssetMaintenanceRecordFormPage })),
)
const AssetMaintenanceRecordsPage = lazy(() =>
  import('@/features/operation/pages/AssetMaintenanceRecordsPage').then((m) => ({ default: m.AssetMaintenanceRecordsPage })),
)
const AssetsPage = lazy(() =>
  import('@/features/operation/pages/AssetsPage').then((m) => ({ default: m.AssetsPage })),
)
const CommonAreaDetailPage = lazy(() =>
  import('@/features/operation/pages/CommonAreaDetailPage').then((m) => ({ default: m.CommonAreaDetailPage })),
)
const CommonAreaFormPage = lazy(() =>
  import('@/features/operation/pages/CommonAreaFormPage').then((m) => ({ default: m.CommonAreaFormPage })),
)
const CommonAreaReservationDetailPage = lazy(() =>
  import('@/features/operation/pages/CommonAreaReservationDetailPage').then((m) => ({ default: m.CommonAreaReservationDetailPage })),
)
const CommonAreaReservationFormPage = lazy(() =>
  import('@/features/operation/pages/CommonAreaReservationFormPage').then((m) => ({ default: m.CommonAreaReservationFormPage })),
)
const CommonAreaReservationsPage = lazy(() =>
  import('@/features/operation/pages/CommonAreaReservationsPage').then((m) => ({ default: m.CommonAreaReservationsPage })),
)
const CommonAreasPage = lazy(() =>
  import('@/features/operation/pages/CommonAreasPage').then((m) => ({ default: m.CommonAreasPage })),
)
const OperationsDashboardPage = lazy(() =>
  import('@/features/operation/pages/OperationsDashboardPage').then((m) => ({ default: m.OperationsDashboardPage })),
)
const ServiceRequestDetailPage = lazy(() =>
  import('@/features/operation/pages/ServiceRequestDetailPage').then((m) => ({ default: m.ServiceRequestDetailPage })),
)
const ServiceRequestFormPage = lazy(() =>
  import('@/features/operation/pages/ServiceRequestFormPage').then((m) => ({ default: m.ServiceRequestFormPage })),
)
const ServiceRequestsPage = lazy(() =>
  import('@/features/operation/pages/ServiceRequestsPage').then((m) => ({ default: m.ServiceRequestsPage })),
)
const WorkOrderDetailPage = lazy(() =>
  import('@/features/operation/pages/WorkOrderDetailPage').then((m) => ({ default: m.WorkOrderDetailPage })),
)
const WorkOrderFormPage = lazy(() =>
  import('@/features/operation/pages/WorkOrderFormPage').then((m) => ({ default: m.WorkOrderFormPage })),
)
const WorkOrdersPage = lazy(() =>
  import('@/features/operation/pages/WorkOrdersPage').then((m) => ({ default: m.WorkOrdersPage })),
)
const ResidentContactsPage = lazy(() =>
  import('@/features/resident/ResidentContactsPage').then((m) => ({ default: m.ResidentContactsPage })),
)
const ResidentDetailPage = lazy(() =>
  import('@/features/resident/ResidentDetailPage').then((m) => ({ default: m.ResidentDetailPage })),
)
const ResidentFormPage = lazy(() =>
  import('@/features/resident/ResidentFormPage').then((m) => ({ default: m.ResidentFormPage })),
)
const ResidentsPage = lazy(() =>
  import('@/features/resident/ResidentsPage').then((m) => ({ default: m.ResidentsPage })),
)
const ResidentVehiclesPage = lazy(() =>
  import('@/features/resident/ResidentVehiclesPage').then((m) => ({ default: m.ResidentVehiclesPage })),
)
const UnitOccupanciesPage = lazy(() =>
  import('@/features/resident/UnitOccupanciesPage').then((m) => ({ default: m.UnitOccupanciesPage })),
)
const BlockDetailPage = lazy(() =>
  import('@/features/site/pages/BlockDetailPage').then((m) => ({ default: m.BlockDetailPage })),
)
const BlockFormPage = lazy(() =>
  import('@/features/site/pages/BlockFormPage').then((m) => ({ default: m.BlockFormPage })),
)
const BlocksPage = lazy(() =>
  import('@/features/site/pages/BlocksPage').then((m) => ({ default: m.BlocksPage })),
)
const FloorDetailPage = lazy(() =>
  import('@/features/site/pages/FloorDetailPage').then((m) => ({ default: m.FloorDetailPage })),
)
const FloorFormPage = lazy(() =>
  import('@/features/site/pages/FloorFormPage').then((m) => ({ default: m.FloorFormPage })),
)
const FloorsPage = lazy(() =>
  import('@/features/site/pages/FloorsPage').then((m) => ({ default: m.FloorsPage })),
)
const SiteDetailPage = lazy(() =>
  import('@/features/site/pages/SiteDetailPage').then((m) => ({ default: m.SiteDetailPage })),
)
const SiteFormPage = lazy(() =>
  import('@/features/site/pages/SiteFormPage').then((m) => ({ default: m.SiteFormPage })),
)
const SitesPage = lazy(() =>
  import('@/features/site/pages/SitesPage').then((m) => ({ default: m.SitesPage })),
)
const UnitDetailPage = lazy(() =>
  import('@/features/site/pages/UnitDetailPage').then((m) => ({ default: m.UnitDetailPage })),
)
const UnitFormPage = lazy(() =>
  import('@/features/site/pages/UnitFormPage').then((m) => ({ default: m.UnitFormPage })),
)
const UnitsPage = lazy(() =>
  import('@/features/site/pages/UnitsPage').then((m) => ({ default: m.UnitsPage })),
)
const RolesPage = lazy(() => import('@/features/system/RolesPage').then((m) => ({ default: m.RolesPage })))
const DashboardLayout = lazy(() =>
  import('@/shared/components/layout/DashboardLayout').then((m) => ({ default: m.DashboardLayout })),
)

function withSuspense(element: ReactNode) {
  return (
    <Suspense fallback={<div className="p-4 text-sm text-zinc-500">Yukleniyor...</div>}>
      {element}
    </Suspense>
  )
}

export function AppRoutes() {
  return (
    <Routes>
      <Route path="/login" element={<LoginPage />} />
      <Route element={<ProtectedRoute />}>
        <Route element={withSuspense(<DashboardLayout />)}>
          <Route path="/dashboard" element={<DashboardHomePage />} />

          <Route path="/sites/new" element={<SiteFormPage mode="create" />} />
          <Route path="/sites/:siteId/blocks/new" element={<BlockFormPage mode="create" />} />
          <Route path="/sites/:siteId/blocks" element={<BlocksPage />} />
          <Route path="/sites/:id/edit" element={<SiteFormPage mode="edit" />} />
          <Route path="/sites/:id" element={<SiteDetailPage />} />
          <Route path="/sites" element={<SitesPage />} />

          <Route path="/blocks/:blockId/floors/new" element={<FloorFormPage mode="create" />} />
          <Route path="/blocks/:blockId/floors" element={<FloorsPage />} />
          <Route path="/blocks/:id/edit" element={<BlockFormPage mode="edit" />} />
          <Route path="/blocks/:id" element={<BlockDetailPage />} />

          <Route path="/floors/:floorId/units/new" element={<UnitFormPage mode="create" />} />
          <Route path="/floors/:floorId/units" element={<UnitsPage />} />
          <Route path="/floors/:id/edit" element={<FloorFormPage mode="edit" />} />
          <Route path="/floors/:id" element={<FloorDetailPage />} />

          <Route path="/units/:id/edit" element={<UnitFormPage mode="edit" />} />
          <Route path="/units/:id" element={<UnitDetailPage />} />
          <Route path="/units/:unitId/occupancies" element={<UnitOccupanciesPage />} />

          <Route path="/residents/new" element={<ResidentFormPage mode="create" />} />
          <Route path="/residents/:id/edit" element={<ResidentFormPage mode="edit" />} />
          <Route path="/residents/:id" element={<ResidentDetailPage />} />
          <Route path="/residents/:residentId/contacts" element={<ResidentContactsPage />} />
          <Route path="/residents/:residentId/vehicles" element={<ResidentVehiclesPage />} />
          <Route path="/residents" element={<ResidentsPage />} />
          <Route path="/finance" element={<FinanceDashboardPage />} />
          <Route path="/finance/due-definitions" element={<DueDefinitionsPage />} />
          <Route path="/finance/due-definitions/new" element={<DueDefinitionFormPage mode="create" />} />
          <Route path="/finance/due-definitions/:id" element={<DueDefinitionDetailPage />} />
          <Route path="/finance/due-definitions/:id/edit" element={<DueDefinitionFormPage mode="edit" />} />

          <Route path="/finance/due-periods" element={<DuePeriodsPage />} />
          <Route path="/finance/due-periods/new" element={<DuePeriodFormPage mode="create" />} />
          <Route path="/finance/due-periods/:id" element={<DuePeriodDetailPage />} />
          <Route path="/finance/due-periods/:id/edit" element={<DuePeriodFormPage mode="edit" />} />

          <Route path="/finance/due-items" element={<DueItemsPage />} />
          <Route path="/finance/due-items/:id" element={<DueItemDetailPage />} />

          <Route path="/finance/payments" element={<PaymentsPage />} />
          <Route path="/finance/payments/new" element={<PaymentFormPage />} />
          <Route path="/finance/payments/:id" element={<PaymentDetailPage />} />

          <Route path="/finance/deposits" element={<DepositsPage />} />
          <Route path="/finance/deposits/new" element={<DepositFormPage />} />
          <Route path="/finance/deposits/:id" element={<DepositDetailPage />} />
          <Route path="/operations" element={<OperationsDashboardPage />} />
          <Route path="/operations/service-requests" element={<ServiceRequestsPage />} />
          <Route path="/operations/service-requests/new" element={<ServiceRequestFormPage mode="create" />} />
          <Route path="/operations/service-requests/:id" element={<ServiceRequestDetailPage />} />
          <Route path="/operations/service-requests/:id/edit" element={<ServiceRequestFormPage mode="edit" />} />

          <Route path="/operations/work-orders" element={<WorkOrdersPage />} />
          <Route path="/operations/work-orders/new" element={<WorkOrderFormPage mode="create" />} />
          <Route path="/operations/work-orders/:id" element={<WorkOrderDetailPage />} />
          <Route path="/operations/work-orders/:id/edit" element={<WorkOrderFormPage mode="edit" />} />

          <Route path="/operations/common-areas" element={<CommonAreasPage />} />
          <Route path="/operations/common-areas/new" element={<CommonAreaFormPage mode="create" />} />
          <Route path="/operations/common-areas/:id" element={<CommonAreaDetailPage />} />
          <Route path="/operations/common-areas/:id/edit" element={<CommonAreaFormPage mode="edit" />} />

          <Route path="/operations/common-area-reservations" element={<CommonAreaReservationsPage />} />
          <Route path="/operations/common-area-reservations/new" element={<CommonAreaReservationFormPage mode="create" />} />
          <Route path="/operations/common-area-reservations/:id" element={<CommonAreaReservationDetailPage />} />
          <Route path="/operations/common-area-reservations/:id/edit" element={<CommonAreaReservationFormPage mode="edit" />} />

          <Route path="/operations/assets" element={<AssetsPage />} />
          <Route path="/operations/assets/new" element={<AssetFormPage mode="create" />} />
          <Route path="/operations/assets/:id" element={<AssetDetailPage />} />
          <Route path="/operations/assets/:id/edit" element={<AssetFormPage mode="edit" />} />

          <Route path="/operations/asset-maintenance-plans" element={<AssetMaintenancePlansPage />} />
          <Route path="/operations/asset-maintenance-plans/new" element={<AssetMaintenancePlanFormPage mode="create" />} />
          <Route path="/operations/asset-maintenance-plans/:id" element={<AssetMaintenancePlanDetailPage />} />
          <Route path="/operations/asset-maintenance-plans/:id/edit" element={<AssetMaintenancePlanFormPage mode="edit" />} />

          <Route path="/operations/asset-maintenance-records" element={<AssetMaintenanceRecordsPage />} />
          <Route path="/operations/asset-maintenance-records/new" element={<AssetMaintenanceRecordFormPage />} />
          <Route path="/operations/asset-maintenance-records/:id" element={<AssetMaintenanceRecordDetailPage />} />
          <Route
            path="/communication/announcements"
            element={<AnnouncementsPage />}
          />
          <Route
            path="/communication/notifications"
            element={<NotificationsPage />}
          />
          <Route path="/system/roles" element={<RolesPage />} />
        </Route>
      </Route>
      <Route path="/" element={<Navigate to="/dashboard" replace />} />
      <Route path="*" element={<Navigate to="/dashboard" replace />} />
    </Routes>
  )
}
