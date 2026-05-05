import { apiRequest } from '@/shared/api/client'

import type { LookupResident, LookupUnit } from '@/features/finance/types'

export async function listLookupResidents(): Promise<LookupResident[]> {
  const res = await apiRequest<{ items: LookupResident[] }>('/residents?page=1&per_page=100')
  return res.items ?? []
}

export async function listLookupUnits(): Promise<LookupUnit[]> {
  const res = await apiRequest<{ items: LookupUnit[] }>('/units?page=1&per_page=100')
  return res.items ?? []
}
