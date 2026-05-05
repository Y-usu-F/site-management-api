import { useQuery } from '@tanstack/react-query'

import { listDuePeriods } from '@/features/finance/api/duePeriodApi'

export function useDuePeriodsQuery(
  params: { page?: number; per_page?: number; status?: string; site_id?: number },
  enabled = true,
) {
  return useQuery({
    queryKey: ['due-periods', params],
    queryFn: () => listDuePeriods(params),
    enabled,
  })
}
