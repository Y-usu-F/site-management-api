import { useQuery } from '@tanstack/react-query'

import { getOperationsSummary } from '@/features/operation/api/operationsSummaryApi'

export function useOperationsSummaryQuery(enabled = true) {
  return useQuery({
    queryKey: ['operations', 'summary'],
    queryFn: getOperationsSummary,
    enabled,
  })
}

