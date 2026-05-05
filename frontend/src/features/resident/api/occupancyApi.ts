import { apiRequest } from '@/shared/api/client'
import { buildQueryString } from '@/shared/lib/buildQueryString'

import type {
  OccupancyListParams,
  OccupancyListResponse,
  OccupancyPayload,
  UnitOccupancy,
} from '@/features/resident/types'

export async function listOccupancies(params?: OccupancyListParams): Promise<OccupancyListResponse> {
  const qs = buildQueryString({
    page: params?.page,
    per_page: params?.per_page,
    unit_id: params?.unit_id,
    resident_profile_id: params?.resident_profile_id,
    status: params?.status,
  })
  return apiRequest<OccupancyListResponse>(`/unit-occupancies${qs}`)
}

export async function createOccupancy(body: OccupancyPayload): Promise<UnitOccupancy> {
  return apiRequest<UnitOccupancy>('/unit-occupancies', {
    method: 'POST',
    body: JSON.stringify(body),
  })
}

export async function updateOccupancy(
  id: number,
  body: Partial<OccupancyPayload>,
): Promise<UnitOccupancy> {
  return apiRequest<UnitOccupancy>(`/unit-occupancies/${id}`, {
    method: 'PUT',
    body: JSON.stringify(body),
  })
}

export async function deleteOccupancy(id: number): Promise<{ id: number }> {
  return apiRequest<{ id: number }>(`/unit-occupancies/${id}`, {
    method: 'DELETE',
  })
}
