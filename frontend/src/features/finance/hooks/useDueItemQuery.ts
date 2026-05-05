import { useQuery } from '@tanstack/react-query'

import { getDueItem } from '@/features/finance/api/dueItemApi'

export function dueItemDetailQueryKey(id: number) {
  return ['due-items', 'detail', id] as const
}

export function useDueItemQuery(id: number, enabled = true) {
  return useQuery({
    queryKey: dueItemDetailQueryKey(id),
    queryFn: () => getDueItem(id),
    enabled,
  })
}
