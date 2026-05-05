import { useQuery } from '@tanstack/react-query'

import { listDeposits } from '@/features/finance/api/depositApi'

export function useDepositsQuery(
  params: {
    page?: number
    per_page?: number
    status?: string
    unit_id?: number
    resident_profile_id?: number
    site_id?: number
  },
  enabled = true,
) {
  return useQuery({
    queryKey: ['deposits', params],
    queryFn: () => listDeposits(params),
    enabled,
  })
}
