import { useMutation, useQueryClient } from '@tanstack/react-query'

import { createBlock, deleteBlock, updateBlock } from '@/features/site/api/blockApi'
import type { BlockCreatePayload, BlockUpdatePayload } from '@/features/site/types'

import { blockDetailQueryKey } from '@/features/site/hooks/useBlockQuery'

export function useCreateBlockMutation() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (body: BlockCreatePayload) => createBlock(body),
    onSuccess: () => {
      void qc.invalidateQueries({ queryKey: ['blocks'] })
    },
  })
}

export function useUpdateBlockMutation() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: ({ id, body }: { id: number; body: BlockUpdatePayload }) =>
      updateBlock(id, body),
    onSuccess: (_data, { id }) => {
      void qc.invalidateQueries({ queryKey: ['blocks'] })
      void qc.invalidateQueries({ queryKey: blockDetailQueryKey(id) })
    },
  })
}

export function useDeleteBlockMutation() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (id: number) => deleteBlock(id),
    onSuccess: (_data, id) => {
      void qc.invalidateQueries({ queryKey: ['blocks'] })
      void qc.removeQueries({ queryKey: blockDetailQueryKey(id) })
      void qc.invalidateQueries({ queryKey: ['floors'] })
    },
  })
}
