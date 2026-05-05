import type { PaginatedResponse } from '@/shared/types/pagination'

/** GET /sites row shape (list selects a subset of columns). */
export interface Site {
  id: number
  public_id?: string | null
  company_id?: number | null
  name: string
  code: string
  address?: string | null
  city?: string | null
  district?: string | null
  status: string
  created_at?: string | null
  updated_at?: string | null
}

export interface SiteListParams {
  page?: number
  per_page?: number
  search?: string
}

export type SiteListResponse = PaginatedResponse<Site>

export interface SiteCreatePayload {
  name: string
  code: string
  address?: string | null
  status?: string
}

export type SiteUpdatePayload = Partial<SiteCreatePayload>

export interface Block {
  id: number
  site_id: number
  name: string
  code: string
  sort_order?: number | null
  status: string
  created_at?: string | null
  updated_at?: string | null
}

export interface BlockListParams {
  page?: number
  per_page?: number
  search?: string
  site_id: number
}

export type BlockListResponse = PaginatedResponse<Block>

export interface BlockCreatePayload {
  site_id: number
  name: string
  code: string
  sort_order?: number | null
  status?: string
}

export type BlockUpdatePayload = Partial<BlockCreatePayload>

export interface Floor {
  id: number
  site_id: number
  block_id: number
  number: number
  label?: string | null
  sort_order?: number | null
  status: string
  created_at?: string | null
  updated_at?: string | null
}

export interface FloorListParams {
  page?: number
  per_page?: number
  search?: string
  block_id: number
}

export type FloorListResponse = PaginatedResponse<Floor>

export interface FloorCreatePayload {
  site_id: number
  block_id: number
  number: number
  label?: string | null
  sort_order?: number | null
  status?: string
}

export type FloorUpdatePayload = Partial<FloorCreatePayload>

export interface Unit {
  id: number
  site_id: number
  block_id: number
  floor_id: number
  unit_no: string
  type?: string | null
  gross_area?: string | number | null
  net_area?: string | number | null
  land_share?: string | number | null
  occupant_name?: string | null
  status: string
  created_at?: string | null
  updated_at?: string | null
}

export interface UnitListParams {
  page?: number
  per_page?: number
  search?: string
  floor_id: number
}

export type UnitListResponse = PaginatedResponse<Unit>

export interface UnitCreatePayload {
  site_id: number
  block_id: number
  floor_id: number
  unit_no: string
  type?: string | null
  gross_area?: string | number | null
  net_area?: string | number | null
  land_share?: string | number | null
  occupant_name?: string | null
  status?: string
}

export type UnitUpdatePayload = Partial<UnitCreatePayload>
