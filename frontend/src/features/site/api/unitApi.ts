import type {
  Unit,
  UnitCreatePayload,
  UnitListParams,
  UnitListResponse,
  UnitUpdatePayload,
} from '@/features/site/types'
import { apiRequest } from '@/shared/api/client'
import { downloadFromApi } from '@/shared/api/fileDownload'
import { buildQueryString } from '@/shared/lib/buildQueryString'
import type { ImportResult } from '@/features/site/api/siteApi'

export async function listUnits(params: UnitListParams): Promise<UnitListResponse> {
  const qs = buildQueryString({
    page: params.page,
    per_page: params.per_page,
    search: params.search?.trim() ? params.search.trim() : undefined,
    floor_id: params.floor_id,
  })
  return apiRequest<UnitListResponse>(`/units${qs}`)
}

export async function getUnit(id: number): Promise<Unit> {
  return apiRequest<Unit>(`/units/${id}`)
}

export async function createUnit(body: UnitCreatePayload): Promise<Unit> {
  return apiRequest<Unit>('/units', {
    method: 'POST',
    body: JSON.stringify(body),
  })
}

export async function updateUnit(id: number, body: UnitUpdatePayload): Promise<Unit> {
  return apiRequest<Unit>(`/units/${id}`, {
    method: 'PUT',
    body: JSON.stringify(body),
  })
}

export async function deleteUnit(id: number): Promise<{ id: number }> {
  return apiRequest<{ id: number }>(`/units/${id}`, {
    method: 'DELETE',
  })
}

export async function bulkDeleteUnits(ids: number[]): Promise<{ ids: number[] }> {
  return apiRequest<{ ids: number[] }>('/units/bulk', {
    method: 'DELETE',
    body: JSON.stringify({ ids }),
  })
}

export async function exportUnitsExcel(params: UnitListParams): Promise<void> {
  const qs = buildQueryString({
    floor_id: params.floor_id,
    search: params.search?.trim() ? params.search.trim() : undefined,
    page: params.page,
    per_page: params.per_page,
  })
  await downloadFromApi(`/units/export${qs}`, 'units.xlsx')
}

export async function importUnitsExcel(file: File): Promise<ImportResult> {
  const form = new FormData()
  form.append('file', file)
  return apiRequest<ImportResult>('/units/import', {
    method: 'POST',
    body: form,
  })
}

export async function downloadUnitTemplate(): Promise<void> {
  await downloadFromApi('/units/import-template', 'units-import-template.xlsx')
}
