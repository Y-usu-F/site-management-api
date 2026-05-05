import { apiRequest } from '@/shared/api/client'
import { buildQueryString } from '@/shared/lib/buildQueryString'

import type {
  ResidentCreatePayload,
  ResidentListParams,
  ResidentListResponse,
  ResidentProfile,
  ResidentUpdatePayload,
} from '@/features/resident/types'

export async function listResidents(params?: ResidentListParams): Promise<ResidentListResponse> {
  const qs = buildQueryString({
    page: params?.page,
    per_page: params?.per_page,
    search: params?.search?.trim() ? params.search.trim() : undefined,
    status: params?.status,
  })
  return apiRequest<ResidentListResponse>(`/residents${qs}`)
}

export async function getResident(id: number): Promise<ResidentProfile> {
  return apiRequest<ResidentProfile>(`/residents/${id}`)
}

export async function createResident(body: ResidentCreatePayload): Promise<ResidentProfile> {
  return apiRequest<ResidentProfile>('/residents', {
    method: 'POST',
    body: JSON.stringify(body),
  })
}

export async function updateResident(
  id: number,
  body: ResidentUpdatePayload,
): Promise<ResidentProfile> {
  return apiRequest<ResidentProfile>(`/residents/${id}`, {
    method: 'PUT',
    body: JSON.stringify(body),
  })
}

export async function deleteResident(id: number): Promise<{ id: number }> {
  return apiRequest<{ id: number }>(`/residents/${id}`, {
    method: 'DELETE',
  })
}
