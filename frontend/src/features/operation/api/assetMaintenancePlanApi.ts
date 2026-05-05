import { apiRequest } from '@/shared/api/client'
import { buildQueryString } from '@/shared/lib/buildQueryString'

import type { AssetMaintenancePlan, AssetMaintenancePlanListResponse } from '@/features/operation/types'

export async function listAssetMaintenancePlans(params?: {
  page?: number
  per_page?: number
  search?: string
  asset_id?: number
  status?: string
}): Promise<AssetMaintenancePlanListResponse> {
  const qs = buildQueryString({
    page: params?.page,
    per_page: params?.per_page,
    search: params?.search?.trim() ? params.search.trim() : undefined,
    asset_id: params?.asset_id,
    status: params?.status,
  })
  return apiRequest<AssetMaintenancePlanListResponse>(`/asset-maintenance-plans${qs}`)
}

export async function getAssetMaintenancePlan(id: number): Promise<AssetMaintenancePlan> {
  return apiRequest<AssetMaintenancePlan>(`/asset-maintenance-plans/${id}`)
}

export async function createAssetMaintenancePlan(
  body: Record<string, unknown>,
): Promise<AssetMaintenancePlan> {
  return apiRequest<AssetMaintenancePlan>('/asset-maintenance-plans', {
    method: 'POST',
    body: JSON.stringify(body),
  })
}

export async function updateAssetMaintenancePlan(
  id: number,
  body: Record<string, unknown>,
): Promise<AssetMaintenancePlan> {
  return apiRequest<AssetMaintenancePlan>(`/asset-maintenance-plans/${id}`, {
    method: 'PUT',
    body: JSON.stringify(body),
  })
}
