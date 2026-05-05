import { apiRequest } from '@/shared/api/client'
import { buildQueryString } from '@/shared/lib/buildQueryString'

import type { DueItem, DueItemListResponse } from '@/features/finance/types'

export async function listDueItems(params?: {
  page?: number
  per_page?: number
  status?: string
  unit_id?: number
  due_period_id?: number
  site_id?: number
}): Promise<DueItemListResponse> {
  const qs = buildQueryString({
    page: params?.page,
    per_page: params?.per_page,
    status: params?.status,
    unit_id: params?.unit_id,
    due_period_id: params?.due_period_id,
    site_id: params?.site_id,
  })
  return apiRequest<DueItemListResponse>(`/due-items${qs}`)
}

export async function getDueItem(id: number): Promise<DueItem> {
  return apiRequest<DueItem>(`/due-items/${id}`)
}

export async function updateDueItem(
  id: number,
  body: { paid_amount?: number; description?: string },
): Promise<DueItem> {
  return apiRequest<DueItem>(`/due-items/${id}`, {
    method: 'PUT',
    body: JSON.stringify(body),
  })
}

export async function cancelDueItem(id: number): Promise<DueItem> {
  return apiRequest<DueItem>(`/due-items/${id}/cancel`, {
    method: 'POST',
    body: JSON.stringify({}),
  })
}
