import type { PaginatedResponse } from '@/shared/types/pagination'

export interface ResidentProfile {
  id: number
  company_id?: number | null
  user_id?: number | null
  first_name: string
  last_name: string
  identity_number?: string | null
  phone?: string | null
  email?: string | null
  birth_date?: string | null
  status: 'active' | 'passive' | string
  created_at?: string | null
  updated_at?: string | null
}

export interface ResidentListParams {
  page?: number
  per_page?: number
  search?: string
  status?: string
}

export type ResidentListResponse = PaginatedResponse<ResidentProfile>

export interface ResidentCreatePayload {
  first_name: string
  last_name: string
  identity_number?: string | null
  phone?: string | null
  email?: string | null
  status?: string
}

export type ResidentUpdatePayload = Partial<ResidentCreatePayload>

export interface UnitOccupancy {
  id: number
  unit_id: number
  resident_profile_id: number
  relationship_type: 'owner' | 'tenant' | 'resident' | 'family_member' | string
  start_date: string
  end_date?: string | null
  is_primary: 0 | 1 | boolean
  status: 'active' | 'passive' | string
  created_at?: string | null
  updated_at?: string | null
}

export interface OccupancyListParams {
  page?: number
  per_page?: number
  unit_id?: number
  resident_profile_id?: number
  status?: string
}

export type OccupancyListResponse = PaginatedResponse<UnitOccupancy>

export interface OccupancyPayload {
  unit_id: number
  resident_profile_id: number
  relationship_type: string
  start_date: string
  end_date?: string | null
  is_primary?: boolean
  status?: string
}

export interface ResidentContact {
  id: number
  resident_profile_id: number
  type: 'phone' | 'email' | 'emergency' | string
  label?: string | null
  value: string
  is_primary: 0 | 1 | boolean
  created_at?: string | null
  updated_at?: string | null
}

export interface ContactListParams {
  page?: number
  per_page?: number
  resident_profile_id: number
}

export type ContactListResponse = PaginatedResponse<ResidentContact>

export interface ContactPayload {
  resident_profile_id: number
  type: string
  label?: string | null
  value: string
  is_primary?: boolean
}

export interface ResidentVehicle {
  id: number
  resident_profile_id: number
  unit_id?: number | null
  plate_number: string
  brand?: string | null
  model?: string | null
  color?: string | null
  status: 'active' | 'passive' | string
  created_at?: string | null
  updated_at?: string | null
}

export interface VehicleListParams {
  page?: number
  per_page?: number
  resident_profile_id: number
  status?: string
}

export type VehicleListResponse = PaginatedResponse<ResidentVehicle>

export interface VehiclePayload {
  resident_profile_id: number
  unit_id?: number | null
  plate_number: string
  brand?: string | null
  model?: string | null
  color?: string | null
  status?: string
}
