import { useQuery } from '@tanstack/react-query'

import { getDueDefinition } from '@/features/finance/api/dueDefinitionApi'

export function dueDefinitionDetailQueryKey(id: number) {
  return ['due-definitions', 'detail', id] as const
}

export function useDueDefinitionQuery(id: number, enabled = true) {
  return useQuery({
    queryKey: dueDefinitionDetailQueryKey(id),
    queryFn: () => getDueDefinition(id),
    enabled,
  })
}
