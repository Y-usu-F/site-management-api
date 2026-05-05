import { apiRequest } from '@/shared/api/client'
import { buildQueryString } from '@/shared/lib/buildQueryString'

import type {
  CommonAreaReservation,
  CommonAreaReservationListResponse,
} from '@/features/operation/types'

export async function listCommonAreaReservations(params?: {
  page?: number
  per_page?: number
  search?: string
  common_area_id?: number
  status?: string
}): Promise<CommonAreaReservationListResponse> {
  const qs = buildQueryString({
    page: params?.page,
    per_page: params?.per_page,
    search: params?.search?.trim() ? params.search.trim() : undefined,
    common_area_id: params?.common_area_id,
    status: params?.status,
  })
  return apiRequest<CommonAreaReservationListResponse>(`/common-area-reservations${qs}`)
}

export async function getCommonAreaReservation(id: number): Promise<CommonAreaReservation> {
  return apiRequest<CommonAreaReservation>(`/common-area-reservations/${id}`)
}

export async function createCommonAreaReservation(
  body: Record<string, unknown>,
): Promise<CommonAreaReservation> {
  return apiRequest<CommonAreaReservation>('/common-area-reservations', {
    method: 'POST',
    body: JSON.stringify(body),
  })
}

export async function updateCommonAreaReservation(
  id: number,
  body: Record<string, unknown>,
): Promise<CommonAreaReservation> {
  return apiRequest<CommonAreaReservation>(`/common-area-reservations/${id}`, {
    method: 'PUT',
    body: JSON.stringify(body),
  })
}
