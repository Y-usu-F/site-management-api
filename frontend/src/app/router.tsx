import { Navigate, Route, Routes } from 'react-router-dom'

import { LoginPage } from '@/features/auth/LoginPage'
import { ProtectedRoute } from '@/features/auth/ProtectedRoute'
import { AnnouncementsPage } from '@/features/communication/AnnouncementsPage'
import { DashboardHomePage } from '@/features/dashboard/DashboardHomePage'
import { DepositDetailPage } from '@/features/finance/pages/DepositDetailPage'
import { DepositFormPage } from '@/features/finance/pages/DepositFormPage'
import { DepositsPage } from '@/features/finance/pages/DepositsPage'
import { DueDefinitionDetailPage } from '@/features/finance/pages/DueDefinitionDetailPage'
import { DueDefinitionFormPage } from '@/features/finance/pages/DueDefinitionFormPage'
import { DueDefinitionsPage } from '@/features/finance/pages/DueDefinitionsPage'
import { DueItemDetailPage } from '@/features/finance/pages/DueItemDetailPage'
import { DueItemsPage } from '@/features/finance/pages/DueItemsPage'
import { DuePeriodDetailPage } from '@/features/finance/pages/DuePeriodDetailPage'
import { DuePeriodFormPage } from '@/features/finance/pages/DuePeriodFormPage'
import { DuePeriodsPage } from '@/features/finance/pages/DuePeriodsPage'
import { FinanceDashboardPage } from '@/features/finance/pages/FinanceDashboardPage'
import { PaymentDetailPage } from '@/features/finance/pages/PaymentDetailPage'
import { PaymentFormPage } from '@/features/finance/pages/PaymentFormPage'
import { PaymentsPage } from '@/features/finance/pages/PaymentsPage'
import { AssetDetailPage } from '@/features/operation/pages/AssetDetailPage'
import { AssetFormPage } from '@/features/operation/pages/AssetFormPage'
import { AssetMaintenancePlanDetailPage } from '@/features/operation/pages/AssetMaintenancePlanDetailPage'
import { AssetMaintenancePlanFormPage } from '@/features/operation/pages/AssetMaintenancePlanFormPage'
import { AssetMaintenancePlansPage } from '@/features/operation/pages/AssetMaintenancePlansPage'
import { AssetMaintenanceRecordDetailPage } from '@/features/operation/pages/AssetMaintenanceRecordDetailPage'
import { AssetMaintenanceRecordFormPage } from '@/features/operation/pages/AssetMaintenanceRecordFormPage'
import { AssetMaintenanceRecordsPage } from '@/features/operation/pages/AssetMaintenanceRecordsPage'
import { AssetsPage } from '@/features/operation/pages/AssetsPage'
import { CommonAreaDetailPage } from '@/features/operation/pages/CommonAreaDetailPage'
import { CommonAreaFormPage } from '@/features/operation/pages/CommonAreaFormPage'
import { CommonAreaReservationDetailPage } from '@/features/operation/pages/CommonAreaReservationDetailPage'
import { CommonAreaReservationFormPage } from '@/features/operation/pages/CommonAreaReservationFormPage'
import { CommonAreaReservationsPage } from '@/features/operation/pages/CommonAreaReservationsPage'
import { CommonAreasPage } from '@/features/operation/pages/CommonAreasPage'
import { OperationsDashboardPage } from '@/features/operation/pages/OperationsDashboardPage'
import { ServiceRequestDetailPage } from '@/features/operation/pages/ServiceRequestDetailPage'
import { ServiceRequestFormPage } from '@/features/operation/pages/ServiceRequestFormPage'
import { ServiceRequestsPage } from '@/features/operation/pages/ServiceRequestsPage'
import { WorkOrderDetailPage } from '@/features/operation/pages/WorkOrderDetailPage'
import { WorkOrderFormPage } from '@/features/operation/pages/WorkOrderFormPage'
import { WorkOrdersPage } from '@/features/operation/pages/WorkOrdersPage'
import { ResidentContactsPage } from '@/features/resident/ResidentContactsPage'
import { ResidentDetailPage } from '@/features/resident/ResidentDetailPage'
import { ResidentFormPage } from '@/features/resident/ResidentFormPage'
import { ResidentsPage } from '@/features/resident/ResidentsPage'
import { ResidentVehiclesPage } from '@/features/resident/ResidentVehiclesPage'
import { UnitOccupanciesPage } from '@/features/resident/UnitOccupanciesPage'
import { BlockDetailPage } from '@/features/site/pages/BlockDetailPage'
import { BlockFormPage } from '@/features/site/pages/BlockFormPage'
import { BlocksPage } from '@/features/site/pages/BlocksPage'
import { FloorDetailPage } from '@/features/site/pages/FloorDetailPage'
import { FloorFormPage } from '@/features/site/pages/FloorFormPage'
import { FloorsPage } from '@/features/site/pages/FloorsPage'
import { SiteDetailPage } from '@/features/site/pages/SiteDetailPage'
import { SiteFormPage } from '@/features/site/pages/SiteFormPage'
import { SitesPage } from '@/features/site/pages/SitesPage'
import { UnitDetailPage } from '@/features/site/pages/UnitDetailPage'
import { UnitFormPage } from '@/features/site/pages/UnitFormPage'
import { UnitsPage } from '@/features/site/pages/UnitsPage'
import { RolesPage } from '@/features/system/RolesPage'
import { DashboardLayout } from '@/shared/components/layout/DashboardLayout'

export function AppRoutes() {
  return (
    <Routes>
      <Route path="/login" element={<LoginPage />} />
      <Route element={<ProtectedRoute />}>
        <Route element={<DashboardLayout />}>
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
          <Route path="/system/roles" element={<RolesPage />} />
        </Route>
      </Route>
      <Route path="/" element={<Navigate to="/dashboard" replace />} />
      <Route path="*" element={<Navigate to="/dashboard" replace />} />
    </Routes>
  )
}
