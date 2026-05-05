import type {
  Site,
  SiteCreatePayload,
  SiteListParams,
  SiteListResponse,
  SiteUpdatePayload,
} from '@/features/site/types'
import { apiRequest } from '@/shared/api/client'
import { downloadFromApi } from '@/shared/api/fileDownload'
import { buildQueryString } from '@/shared/lib/buildQueryString'

export async function listSites(params?: SiteListParams): Promise<SiteListResponse> {
  const qs = buildQueryString({
    page: params?.page,
    per_page: params?.per_page,
    search: params?.search?.trim() ? params.search.trim() : undefined,
  })
  return apiRequest<SiteListResponse>(`/sites${qs}`)
}

export async function getSite(id: number): Promise<Site> {
  return apiRequest<Site>(`/sites/${id}`)
}

export async function createSite(body: SiteCreatePayload): Promise<Site> {
  return apiRequest<Site>('/sites', {
    method: 'POST',
    body: JSON.stringify(body),
  })
}

export async function updateSite(id: number, body: SiteUpdatePayload): Promise<Site> {
  return apiRequest<Site>(`/sites/${id}`, {
    method: 'PUT',
    body: JSON.stringify(body),
  })
}

export async function deleteSite(id: number): Promise<{ id: number }> {
  return apiRequest<{ id: number }>(`/sites/${id}`, {
    method: 'DELETE',
  })
}

export async function bulkDeleteSites(ids: number[]): Promise<{ ids: number[] }> {
  return apiRequest<{ ids: number[] }>('/sites/bulk', {
    method: 'DELETE',
    body: JSON.stringify({ ids }),
  })
}

export async function exportSitesExcel(params?: SiteListParams): Promise<void> {
  const qs = buildQueryString({
    search: params?.search?.trim() ? params.search.trim() : undefined,
    page: params?.page,
    per_page: params?.per_page,
  })
  await downloadFromApi(`/sites/export${qs}`, 'sites.xlsx')
}

export interface ImportResult {
  inserted_count?: number
  updated_count?: number
  skipped_count?: number
  error_rows?: unknown[]
}

export async function importSitesExcel(file: File): Promise<ImportResult> {
  const form = new FormData()
  form.append('file', file)
  return apiRequest<ImportResult>('/sites/import', {
    method: 'POST',
    body: form,
  })
}

export async function downloadSiteTemplate(): Promise<void> {
  await downloadFromApi('/sites/import-template', 'sites-import-template.xlsx')
}
