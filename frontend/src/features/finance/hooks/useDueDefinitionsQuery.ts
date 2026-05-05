import { useQuery } from '@tanstack/react-query'

import { listDueDefinitions } from '@/features/finance/api/dueDefinitionApi'

export function useDueDefinitionsQuery(
  params: { page?: number; per_page?: number; search?: string; status?: string },
  enabled = true,
) {
  return useQuery({
    queryKey: ['due-definitions', params],
    queryFn: () => listDueDefinitions(params),
    enabled,
  })
}
