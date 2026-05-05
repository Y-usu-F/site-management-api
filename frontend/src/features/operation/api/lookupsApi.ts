import { apiRequest } from '@/shared/api/client'

export interface LookupOption {
  id: number
  label: string
}

function normalizeLabel(row: Record<string, unknown>, keys: string[]) {
  for (const key of keys) {
    const value = row[key]
    if (typeof value === 'string' && value.trim()) return value
  }
  return `#${String(row.id ?? '')}`
}

async function fetchLookup(path: string, labelKeys: string[]): Promise<LookupOption[]> {
  const res = await apiRequest<{ items?: Array<Record<string, unknown>> }>(`${path}?page=1&per_page=100`)
  return (res.items ?? []).map((item) => ({
    id: Number(item.id),
    label: normalizeLabel(item, labelKeys),
  }))
}

function toLookupPath(path: string, search?: string): string {
  const query = new URLSearchParams({ page: '1', per_page: '100' })
  if (search && search.trim()) {
    query.set('search', search.trim())
  }
  return `${path}?${query.toString()}`
}

export const listLookupSites = (search?: string) => fetchLookup(toLookupPath('/sites', search), ['name', 'code'])
export const listLookupUnits = (search?: string) => fetchLookup(toLookupPath('/units', search), ['unit_no', 'name'])
export const listLookupResidents = (search?: string) =>
  fetchLookup(toLookupPath('/residents', search), ['first_name', 'last_name', 'email'])
export const listLookupAssets = (search?: string) => fetchLookup(toLookupPath('/assets', search), ['name', 'asset_no'])
export const listLookupCommonAreas = (search?: string) =>
  fetchLookup(toLookupPath('/common-areas', search), ['name', 'code'])
