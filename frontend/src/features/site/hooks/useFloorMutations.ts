import { useMutation, useQueryClient } from '@tanstack/react-query'

import { createFloor, deleteFloor, updateFloor } from '@/features/site/api/floorApi'
import type { FloorCreatePayload, FloorUpdatePayload } from '@/features/site/types'

import { floorDetailQueryKey } from '@/features/site/hooks/useFloorQuery'

export function useCreateFloorMutation() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (body: FloorCreatePayload) => createFloor(body),
    onSuccess: () => {
      void qc.invalidateQueries({ queryKey: ['floors'] })
    },
  })
}

export function useUpdateFloorMutation() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: ({ id, body }: { id: number; body: FloorUpdatePayload }) =>
      updateFloor(id, body),
    onSuccess: (_data, { id }) => {
      void qc.invalidateQueries({ queryKey: ['floors'] })
      void qc.invalidateQueries({ queryKey: floorDetailQueryKey(id) })
    },
  })
}

export function useDeleteFloorMutation() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (id: number) => deleteFloor(id),
    onSuccess: (_data, id) => {
      void qc.invalidateQueries({ queryKey: ['floors'] })
      void qc.removeQueries({ queryKey: floorDetailQueryKey(id) })
      void qc.invalidateQueries({ queryKey: ['units'] })
    },
  })
}
