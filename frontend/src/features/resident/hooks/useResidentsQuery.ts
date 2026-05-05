import { useQuery } from '@tanstack/react-query'

import { listResidents } from '@/features/resident/api/residentApi'
import type { ResidentListParams } from '@/features/resident/types'

export function residentsQueryKey(params: ResidentListParams) {
  return ['residents', params] as const
}

export function useResidentsQuery(params: ResidentListParams, enabled = true) {
  return useQuery({
    queryKey: residentsQueryKey(params),
    queryFn: () => listResidents(params),
    enabled,
  })
}
