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

export const listLookupSites = () => fetchLookup('/sites', ['name', 'code'])
export const listLookupUnits = () => fetchLookup('/units', ['unit_no', 'name'])
export const listLookupResidents = () => fetchLookup('/residents', ['first_name', 'last_name', 'email'])
export const listLookupAssets = () => fetchLookup('/assets', ['name', 'asset_no'])
export const listLookupCommonAreas = () => fetchLookup('/common-areas', ['name', 'code'])
