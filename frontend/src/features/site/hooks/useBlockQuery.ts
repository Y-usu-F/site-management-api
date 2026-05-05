import { useQuery } from '@tanstack/react-query'

import { getBlock } from '@/features/site/api/blockApi'

export function blockDetailQueryKey(id: number) {
  return ['block', id] as const
}

interface UseBlockQueryOptions {
  enabled?: boolean
}

export function useBlockQuery(id: number, options?: UseBlockQueryOptions) {
  return useQuery({
    queryKey: blockDetailQueryKey(id),
    queryFn: () => getBlock(id),
    enabled: (options?.enabled ?? true) && id > 0,
  })
}
