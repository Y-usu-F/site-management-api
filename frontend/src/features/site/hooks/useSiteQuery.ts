import { useQuery } from '@tanstack/react-query'

import { getSite } from '@/features/site/api/siteApi'

export function siteDetailQueryKey(id: number) {
  return ['site', id] as const
}

interface UseSiteQueryOptions {
  enabled?: boolean
}

export function useSiteQuery(id: number, options?: UseSiteQueryOptions) {
  return useQuery({
    queryKey: siteDetailQueryKey(id),
    queryFn: () => getSite(id),
    enabled: (options?.enabled ?? true) && Number.isFinite(id) && id > 0,
  })
}
