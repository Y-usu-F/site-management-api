import { apiRequest } from '@/shared/api/client'
import { buildQueryString } from '@/shared/lib/buildQueryString'

import type { WorkOrder, WorkOrderListResponse } from '@/features/operation/types'

export async function listWorkOrders(params?: {
  page?: number
  per_page?: number
  search?: string
  status?: string
}): Promise<WorkOrderListResponse> {
  const qs = buildQueryString({
    page: params?.page,
    per_page: params?.per_page,
    search: params?.search?.trim() ? params.search.trim() : undefined,
    status: params?.status,
  })
  return apiRequest<WorkOrderListResponse>(`/work-orders${qs}`)
}

export async function getWorkOrder(id: number): Promise<WorkOrder> {
  return apiRequest<WorkOrder>(`/work-orders/${id}`)
}

export async function createWorkOrder(body: Record<string, unknown>): Promise<WorkOrder> {
  return apiRequest<WorkOrder>('/work-orders', {
    method: 'POST',
    body: JSON.stringify(body),
  })
}

export async function updateWorkOrder(id: number, body: Record<string, unknown>): Promise<WorkOrder> {
  return apiRequest<WorkOrder>(`/work-orders/${id}`, {
    method: 'PUT',
    body: JSON.stringify(body),
  })
}
