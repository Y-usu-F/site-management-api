import { useQuery } from '@tanstack/react-query'

import { getFloor } from '@/features/site/api/floorApi'

export function floorDetailQueryKey(id: number) {
  return ['floor', id] as const
}

interface UseFloorQueryOptions {
  enabled?: boolean
}

export function useFloorQuery(id: number, options?: UseFloorQueryOptions) {
  return useQuery({
    queryKey: floorDetailQueryKey(id),
    queryFn: () => getFloor(id),
    enabled: (options?.enabled ?? true) && id > 0,
  })
}
