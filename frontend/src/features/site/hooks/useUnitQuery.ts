import { useQuery } from '@tanstack/react-query'

import { getUnit } from '@/features/site/api/unitApi'

export function unitDetailQueryKey(id: number) {
  return ['unit', id] as const
}

interface UseUnitQueryOptions {
  enabled?: boolean
}

export function useUnitQuery(id: number, options?: UseUnitQueryOptions) {
  return useQuery({
    queryKey: unitDetailQueryKey(id),
    queryFn: () => getUnit(id),
    enabled: (options?.enabled ?? true) && id > 0,
  })
}
