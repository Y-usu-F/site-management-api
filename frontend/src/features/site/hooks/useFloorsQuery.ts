import { useQuery } from '@tanstack/react-query'

import { listFloors } from '@/features/site/api/floorApi'
import type { FloorListParams } from '@/features/site/types'

export function floorsQueryKey(blockId: number, params: FloorListParams) {
  return ['floors', blockId, params] as const
}

interface UseFloorsQueryOptions {
  enabled?: boolean
}

export function useFloorsQuery(params: FloorListParams, options?: UseFloorsQueryOptions) {
  return useQuery({
    queryKey: floorsQueryKey(params.block_id, params),
    queryFn: () => listFloors(params),
    enabled: (options?.enabled ?? true) && params.block_id > 0,
  })
}
