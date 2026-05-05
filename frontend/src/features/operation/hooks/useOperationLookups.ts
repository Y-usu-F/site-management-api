import { useQuery } from '@tanstack/react-query'

import {
  listLookupAssets,
  listLookupCommonAreas,
  listLookupResidents,
  listLookupSites,
  listLookupUnits,
  type LookupOption,
} from '@/features/operation/api/lookupsApi'

function toMap(options?: LookupOption[]): Record<number, string> {
  const map: Record<number, string> = {}
  for (const option of options ?? []) {
    map[option.id] = option.label
  }
  return map
}

export function useOperationLookups() {
  const sites = useQuery({ queryKey: ['operation', 'lookup', 'sites', 'all'], queryFn: () => listLookupSites('') })
  const units = useQuery({ queryKey: ['operation', 'lookup', 'units', 'all'], queryFn: () => listLookupUnits('') })
  const residents = useQuery({
    queryKey: ['operation', 'lookup', 'residents', 'all'],
    queryFn: () => listLookupResidents(''),
  })
  const assets = useQuery({ queryKey: ['operation', 'lookup', 'assets', 'all'], queryFn: () => listLookupAssets('') })
  const commonAreas = useQuery({
    queryKey: ['operation', 'lookup', 'common-areas', 'all'],
    queryFn: () => listLookupCommonAreas(''),
  })

  return {
    siteMap: toMap(sites.data),
    unitMap: toMap(units.data),
    residentMap: toMap(residents.data),
    assetMap: toMap(assets.data),
    commonAreaMap: toMap(commonAreas.data),
  }
}

