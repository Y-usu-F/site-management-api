import { useQuery } from '@tanstack/react-query'

import { listDueItems } from '@/features/finance/api/dueItemApi'

export function useDueItemsQuery(
  params: {
    page?: number
    per_page?: number
    status?: string
    unit_id?: number
    due_period_id?: number
    site_id?: number
  },
  enabled = true,
) {
  return useQuery({
    queryKey: ['due-items', params],
    queryFn: () => listDueItems(params),
    enabled,
  })
}
