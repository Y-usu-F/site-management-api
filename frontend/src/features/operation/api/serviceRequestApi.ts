import { apiRequest } from '@/shared/api/client'
import { buildQueryString } from '@/shared/lib/buildQueryString'

import type { ServiceRequest, ServiceRequestListResponse } from '@/features/operation/types'

export async function listServiceRequests(params?: {
  page?: number
  per_page?: number
  search?: string
  site_id?: number
  status?: string
}): Promise<ServiceRequestListResponse> {
  const qs = buildQueryString({
    page: params?.page,
    per_page: params?.per_page,
    search: params?.search?.trim() ? params.search.trim() : undefined,
    site_id: params?.site_id,
    status: params?.status,
  })
  return apiRequest<ServiceRequestListResponse>(`/service-requests${qs}`)
}

export async function getServiceRequest(id: number): Promise<ServiceRequest> {
  return apiRequest<ServiceRequest>(`/service-requests/${id}`)
}

export async function createServiceRequest(body: Record<string, unknown>): Promise<ServiceRequest> {
  return apiRequest<ServiceRequest>('/service-requests', {
    method: 'POST',
    body: JSON.stringify(body),
  })
}

export async function updateServiceRequest(
  id: number,
  body: Record<string, unknown>,
): Promise<ServiceRequest> {
  return apiRequest<ServiceRequest>(`/service-requests/${id}`, {
    method: 'PUT',
    body: JSON.stringify(body),
  })
}
