import { useQuery } from '@tanstack/react-query'

import { listUnits } from '@/features/site/api/unitApi'
import type { UnitListParams } from '@/features/site/types'

export function unitsQueryKey(floorId: number, params: UnitListParams) {
  return ['units', floorId, params] as const
}

interface UseUnitsQueryOptions {
  enabled?: boolean
}

export function useUnitsQuery(params: UnitListParams, options?: UseUnitsQueryOptions) {
  return useQuery({
    queryKey: unitsQueryKey(params.floor_id, params),
    queryFn: () => listUnits(params),
    enabled: (options?.enabled ?? true) && params.floor_id > 0,
  })
}
