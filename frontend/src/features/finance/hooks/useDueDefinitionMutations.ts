import { useMutation, useQueryClient } from '@tanstack/react-query'

import {
  createDueDefinition,
  deleteDueDefinition,
  updateDueDefinition,
} from '@/features/finance/api/dueDefinitionApi'
import { dueDefinitionDetailQueryKey } from '@/features/finance/hooks/useDueDefinitionQuery'
import type { DueDefinitionPayload } from '@/features/finance/types'

export function useCreateDueDefinitionMutation() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (body: DueDefinitionPayload) => createDueDefinition(body),
    onSuccess: () => void qc.invalidateQueries({ queryKey: ['due-definitions'] }),
  })
}

export function useUpdateDueDefinitionMutation() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: ({ id, body }: { id: number; body: Partial<DueDefinitionPayload> }) =>
      updateDueDefinition(id, body),
    onSuccess: (_d, { id }) => {
      void qc.invalidateQueries({ queryKey: ['due-definitions'] })
      void qc.invalidateQueries({ queryKey: dueDefinitionDetailQueryKey(id) })
    },
  })
}

export function useDeleteDueDefinitionMutation() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (id: number) => deleteDueDefinition(id),
    onSuccess: (_d, id) => {
      void qc.invalidateQueries({ queryKey: ['due-definitions'] })
      void qc.removeQueries({ queryKey: dueDefinitionDetailQueryKey(id) })
    },
  })
}
