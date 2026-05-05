import { apiRequest } from '@/shared/api/client'
import { buildQueryString } from '@/shared/lib/buildQueryString'

import type {
  DueDefinition,
  DueDefinitionListResponse,
  DueDefinitionPayload,
} from '@/features/finance/types'

export async function listDueDefinitions(params?: {
  page?: number
  per_page?: number
  search?: string
  status?: string
}): Promise<DueDefinitionListResponse> {
  const qs = buildQueryString({
    page: params?.page,
    per_page: params?.per_page,
    search: params?.search?.trim() ? params.search.trim() : undefined,
    status: params?.status,
  })
  return apiRequest<DueDefinitionListResponse>(`/due-definitions${qs}`)
}

export async function getDueDefinition(id: number): Promise<DueDefinition> {
  return apiRequest<DueDefinition>(`/due-definitions/${id}`)
}

export async function createDueDefinition(body: DueDefinitionPayload): Promise<DueDefinition> {
  return apiRequest<DueDefinition>('/due-definitions', {
    method: 'POST',
    body: JSON.stringify(body),
  })
}

export async function updateDueDefinition(
  id: number,
  body: Partial<DueDefinitionPayload>,
): Promise<DueDefinition> {
  return apiRequest<DueDefinition>(`/due-definitions/${id}`, {
    method: 'PUT',
    body: JSON.stringify(body),
  })
}

export async function deleteDueDefinition(id: number): Promise<{ id: number }> {
  return apiRequest<{ id: number }>(`/due-definitions/${id}`, {
    method: 'DELETE',
  })
}
