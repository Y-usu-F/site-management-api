import { useQuery } from '@tanstack/react-query'

import { listBlocks } from '@/features/site/api/blockApi'
import type { BlockListParams } from '@/features/site/types'

export function blocksQueryKey(siteId: number, params: BlockListParams) {
  return ['blocks', siteId, params] as const
}

interface UseBlocksQueryOptions {
  enabled?: boolean
}

export function useBlocksQuery(params: BlockListParams, options?: UseBlocksQueryOptions) {
  return useQuery({
    queryKey: blocksQueryKey(params.site_id, params),
    queryFn: () => listBlocks(params),
    enabled: (options?.enabled ?? true) && params.site_id > 0,
  })
}
