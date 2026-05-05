import type {
  Floor,
  FloorCreatePayload,
  FloorListParams,
  FloorListResponse,
  FloorUpdatePayload,
} from '@/features/site/types'
import { apiRequest } from '@/shared/api/client'
import { downloadFromApi } from '@/shared/api/fileDownload'
import { buildQueryString } from '@/shared/lib/buildQueryString'
import type { ImportResult } from '@/features/site/api/siteApi'

export async function listFloors(params: FloorListParams): Promise<FloorListResponse> {
  const qs = buildQueryString({
    page: params.page,
    per_page: params.per_page,
    search: params.search?.trim() ? params.search.trim() : undefined,
    block_id: params.block_id,
  })
  return apiRequest<FloorListResponse>(`/floors${qs}`)
}

export async function getFloor(id: number): Promise<Floor> {
  return apiRequest<Floor>(`/floors/${id}`)
}

export async function createFloor(body: FloorCreatePayload): Promise<Floor> {
  return apiRequest<Floor>('/floors', {
    method: 'POST',
    body: JSON.stringify(body),
  })
}

export async function updateFloor(id: number, body: FloorUpdatePayload): Promise<Floor> {
  return apiRequest<Floor>(`/floors/${id}`, {
    method: 'PUT',
    body: JSON.stringify(body),
  })
}

export async function deleteFloor(id: number): Promise<{ id: number }> {
  return apiRequest<{ id: number }>(`/floors/${id}`, {
    method: 'DELETE',
  })
}

export async function bulkDeleteFloors(ids: number[]): Promise<{ ids: number[] }> {
  return apiRequest<{ ids: number[] }>('/floors/bulk', {
    method: 'DELETE',
    body: JSON.stringify({ ids }),
  })
}

export async function exportFloorsExcel(params: FloorListParams): Promise<void> {
  const qs = buildQueryString({
    block_id: params.block_id,
    search: params.search?.trim() ? params.search.trim() : undefined,
    page: params.page,
    per_page: params.per_page,
  })
  await downloadFromApi(`/floors/export${qs}`, 'floors.xlsx')
}

export async function importFloorsExcel(file: File): Promise<ImportResult> {
  const form = new FormData()
  form.append('file', file)
  return apiRequest<ImportResult>('/floors/import', {
    method: 'POST',
    body: form,
  })
}

export async function downloadFloorTemplate(): Promise<void> {
  await downloadFromApi('/floors/import-template', 'floors-import-template.xlsx')
}
