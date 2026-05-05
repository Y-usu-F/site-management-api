import { useQuery } from '@tanstack/react-query'

import { listSites } from '@/features/site/api/siteApi'
import type { SiteListParams } from '@/features/site/types'

export function sitesQueryKey(params: SiteListParams) {
  return ['sites', params] as const
}

interface UseSitesQueryOptions {
  enabled?: boolean
}

export function useSitesQuery(params: SiteListParams, options?: UseSitesQueryOptions) {
  return useQuery({
    queryKey: sitesQueryKey(params),
    queryFn: () => listSites(params),
    enabled: options?.enabled ?? true,
  })
}
