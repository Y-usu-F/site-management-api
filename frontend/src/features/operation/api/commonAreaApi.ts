import { apiRequest } from '@/shared/api/client'
import { buildQueryString } from '@/shared/lib/buildQueryString'

import type { CommonArea, CommonAreaListResponse } from '@/features/operation/types'

export async function listCommonAreas(params?: {
  page?: number
  per_page?: number
  search?: string
  site_id?: number
}): Promise<CommonAreaListResponse> {
  const qs = buildQueryString({
    page: params?.page,
    per_page: params?.per_page,
    search: params?.search?.trim() ? params.search.trim() : undefined,
    site_id: params?.site_id,
  })
  return apiRequest<CommonAreaListResponse>(`/common-areas${qs}`)
}

export async function getCommonArea(id: number): Promise<CommonArea> {
  return apiRequest<CommonArea>(`/common-areas/${id}`)
}

export async function createCommonArea(body: Record<string, unknown>): Promise<CommonArea> {
  return apiRequest<CommonArea>('/common-areas', {
    method: 'POST',
    body: JSON.stringify(body),
  })
}

export async function updateCommonArea(id: number, body: Record<string, unknown>): Promise<CommonArea> {
  return apiRequest<CommonArea>(`/common-areas/${id}`, {
    method: 'PUT',
    body: JSON.stringify(body),
  })
}

export async function deleteCommonArea(id: number): Promise<unknown> {
  return apiRequest(`/common-areas/${id}`, { method: 'DELETE' })
}
