import { apiRequest } from '@/shared/api/client'
import { buildQueryString } from '@/shared/lib/buildQueryString'

import type { Asset, AssetListResponse } from '@/features/operation/types'

export async function listAssets(params?: {
  page?: number
  per_page?: number
  search?: string
  site_id?: number
  status?: string
}): Promise<AssetListResponse> {
  const qs = buildQueryString({
    page: params?.page,
    per_page: params?.per_page,
    search: params?.search?.trim() ? params.search.trim() : undefined,
    site_id: params?.site_id,
    status: params?.status,
  })
  return apiRequest<AssetListResponse>(`/assets${qs}`)
}

export async function getAsset(id: number): Promise<Asset> {
  return apiRequest<Asset>(`/assets/${id}`)
}

export async function createAsset(body: Record<string, unknown>): Promise<Asset> {
  return apiRequest<Asset>('/assets', {
    method: 'POST',
    body: JSON.stringify(body),
  })
}

export async function updateAsset(id: number, body: Record<string, unknown>): Promise<Asset> {
  return apiRequest<Asset>(`/assets/${id}`, {
    method: 'PUT',
    body: JSON.stringify(body),
  })
}
