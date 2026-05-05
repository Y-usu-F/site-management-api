import { apiRequest } from '@/shared/api/client'
import { buildQueryString } from '@/shared/lib/buildQueryString'

import type {
  AssetMaintenanceRecord,
  AssetMaintenanceRecordListResponse,
} from '@/features/operation/types'

export async function listAssetMaintenanceRecords(params?: {
  page?: number
  per_page?: number
  search?: string
  asset_id?: number
}): Promise<AssetMaintenanceRecordListResponse> {
  const qs = buildQueryString({
    page: params?.page,
    per_page: params?.per_page,
    search: params?.search?.trim() ? params.search.trim() : undefined,
    asset_id: params?.asset_id,
  })
  return apiRequest<AssetMaintenanceRecordListResponse>(`/asset-maintenance-records${qs}`)
}

export async function getAssetMaintenanceRecord(id: number): Promise<AssetMaintenanceRecord> {
  return apiRequest<AssetMaintenanceRecord>(`/asset-maintenance-records/${id}`)
}

export async function createAssetMaintenanceRecord(
  body: Record<string, unknown>,
): Promise<AssetMaintenanceRecord> {
  return apiRequest<AssetMaintenanceRecord>('/asset-maintenance-records', {
    method: 'POST',
    body: JSON.stringify(body),
  })
}
