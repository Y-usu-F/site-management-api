import type { PaginatedResponse } from '@/shared/types/pagination'

export interface ServiceRequest {
  id: number
  site_id: number
  block_id?: number | null
  unit_id?: number | null
  resident_profile_id?: number | null
  category_id?: number | null
  title: string
  description: string
  priority?: string | null
  source?: string | null
  status?: string | null
}

export interface WorkOrder {
  id: number
  service_request_id: number
  assigned_to_user_id?: number | null
  vendor_name?: string | null
  planned_start_at?: string | null
  planned_end_at?: string | null
  cost_amount?: number | string | null
  currency?: string | null
  notes?: string | null
  status?: string | null
}

export interface CommonArea {
  id: number
  site_id: number
  name: string
  code?: string | null
  description?: string | null
  capacity?: number | null
  requires_approval?: boolean | null
  is_paid?: boolean | null
  fee_amount?: number | string | null
  currency?: string | null
  status?: string | null
}

export interface CommonAreaReservation {
  id: number
  common_area_id: number
  unit_id?: number | null
  resident_profile_id?: number | null
  start_at: string
  end_at: string
  participant_count?: number | null
  notes?: string | null
  status?: string | null
}

export interface Asset {
  id: number
  site_id: number
  block_id?: number | null
  unit_id?: number | null
  asset_no?: string | null
  asset_type: string
  name: string
  brand?: string | null
  model?: string | null
  serial_number?: string | null
  purchase_date?: string | null
  warranty_until?: string | null
  location_note?: string | null
  status?: string | null
}

export interface AssetMaintenancePlan {
  id: number
  asset_id: number
  frequency_type: string
  frequency_interval?: number | null
  next_due_date: string
  vendor_name?: string | null
  notes?: string | null
  status?: string | null
}

export interface AssetMaintenanceRecord {
  id: number
  asset_id: number
  maintenance_plan_id?: number | null
  work_order_id?: number | null
  performed_at: string
  performed_by?: string | null
  vendor_name?: string | null
  cost_amount?: number | string | null
  currency?: string | null
  description?: string | null
  next_due_date?: string | null
  status?: string | null
}

export type ServiceRequestListResponse = PaginatedResponse<ServiceRequest>
export type WorkOrderListResponse = PaginatedResponse<WorkOrder>
export type CommonAreaListResponse = PaginatedResponse<CommonArea>
export type CommonAreaReservationListResponse = PaginatedResponse<CommonAreaReservation>
export type AssetListResponse = PaginatedResponse<Asset>
export type AssetMaintenancePlanListResponse = PaginatedResponse<AssetMaintenancePlan>
export type AssetMaintenanceRecordListResponse = PaginatedResponse<AssetMaintenanceRecord>
