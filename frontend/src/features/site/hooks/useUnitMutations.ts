import { useMutation, useQueryClient } from '@tanstack/react-query'

import { createUnit, deleteUnit, updateUnit } from '@/features/site/api/unitApi'
import type { UnitCreatePayload, UnitUpdatePayload } from '@/features/site/types'

import { unitDetailQueryKey } from '@/features/site/hooks/useUnitQuery'

export function useCreateUnitMutation() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (body: UnitCreatePayload) => createUnit(body),
    onSuccess: () => {
      void qc.invalidateQueries({ queryKey: ['units'] })
    },
  })
}

export function useUpdateUnitMutation() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: ({ id, body }: { id: number; body: UnitUpdatePayload }) =>
      updateUnit(id, body),
    onSuccess: (_data, { id }) => {
      void qc.invalidateQueries({ queryKey: ['units'] })
      void qc.invalidateQueries({ queryKey: unitDetailQueryKey(id) })
    },
  })
}

export function useDeleteUnitMutation() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (id: number) => deleteUnit(id),
    onSuccess: (_data, id) => {
      void qc.invalidateQueries({ queryKey: ['units'] })
      void qc.removeQueries({ queryKey: unitDetailQueryKey(id) })
    },
  })
}
