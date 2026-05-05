import { useMutation, useQueryClient } from '@tanstack/react-query'

import {
  createResident,
  deleteResident,
  updateResident,
} from '@/features/resident/api/residentApi'
import { residentDetailQueryKey } from '@/features/resident/hooks/useResidentQuery'
import type { ResidentCreatePayload, ResidentUpdatePayload } from '@/features/resident/types'

export function useCreateResidentMutation() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (body: ResidentCreatePayload) => createResident(body),
    onSuccess: () => {
      void qc.invalidateQueries({ queryKey: ['residents'] })
    },
  })
}

export function useUpdateResidentMutation() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: ({ id, body }: { id: number; body: ResidentUpdatePayload }) =>
      updateResident(id, body),
    onSuccess: (_data, { id }) => {
      void qc.invalidateQueries({ queryKey: ['residents'] })
      void qc.invalidateQueries({ queryKey: residentDetailQueryKey(id) })
    },
  })
}

export function useDeleteResidentMutation() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (id: number) => deleteResident(id),
    onSuccess: (_data, id) => {
      void qc.invalidateQueries({ queryKey: ['residents'] })
      void qc.removeQueries({ queryKey: residentDetailQueryKey(id) })
      void qc.invalidateQueries({ queryKey: ['resident-contacts'] })
      void qc.invalidateQueries({ queryKey: ['resident-vehicles'] })
      void qc.invalidateQueries({ queryKey: ['unit-occupancies'] })
    },
  })
}
