import { apiRequest } from '@/shared/api/client'
import { buildQueryString } from '@/shared/lib/buildQueryString'

import type { DuePeriod, DuePeriodListResponse, DuePeriodPayload } from '@/features/finance/types'

export async function listDuePeriods(params?: {
  page?: number
  per_page?: number
  status?: string
  site_id?: number
}): Promise<DuePeriodListResponse> {
  const qs = buildQueryString({
    page: params?.page,
    per_page: params?.per_page,
    status: params?.status,
    site_id: params?.site_id,
  })
  return apiRequest<DuePeriodListResponse>(`/due-periods${qs}`)
}

export async function getDuePeriod(id: number): Promise<DuePeriod> {
  return apiRequest<DuePeriod>(`/due-periods/${id}`)
}

export async function createDuePeriod(body: DuePeriodPayload): Promise<DuePeriod> {
  return apiRequest<DuePeriod>('/due-periods', {
    method: 'POST',
    body: JSON.stringify(body),
  })
}

export async function updateDuePeriod(
  id: number,
  body: Partial<DuePeriodPayload>,
): Promise<DuePeriod> {
  return apiRequest<DuePeriod>(`/due-periods/${id}`, {
    method: 'PUT',
    body: JSON.stringify(body),
  })
}

export async function deleteDuePeriod(id: number): Promise<{ id: number }> {
  return apiRequest<{ id: number }>(`/due-periods/${id}`, {
    method: 'DELETE',
  })
}

export async function closeDuePeriod(id: number): Promise<DuePeriod> {
  return apiRequest<DuePeriod>(`/due-periods/${id}/close`, {
    method: 'POST',
    body: JSON.stringify({}),
  })
}

export async function lockDuePeriod(id: number): Promise<DuePeriod> {
  return apiRequest<DuePeriod>(`/due-periods/${id}/lock`, {
    method: 'POST',
    body: JSON.stringify({}),
  })
}
