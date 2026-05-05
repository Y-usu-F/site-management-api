import { useQuery } from '@tanstack/react-query'

import { getDuePeriod } from '@/features/finance/api/duePeriodApi'

export function duePeriodDetailQueryKey(id: number) {
  return ['due-periods', 'detail', id] as const
}

export function useDuePeriodQuery(id: number, enabled = true) {
  return useQuery({
    queryKey: duePeriodDetailQueryKey(id),
    queryFn: () => getDuePeriod(id),
    enabled,
  })
}
