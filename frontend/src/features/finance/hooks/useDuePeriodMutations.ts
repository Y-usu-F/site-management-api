import { useMutation, useQueryClient } from '@tanstack/react-query'

import {
  closeDuePeriod,
  createDuePeriod,
  deleteDuePeriod,
  lockDuePeriod,
  updateDuePeriod,
} from '@/features/finance/api/duePeriodApi'
import { duePeriodDetailQueryKey } from '@/features/finance/hooks/useDuePeriodQuery'
import type { DuePeriodPayload } from '@/features/finance/types'

export function useCreateDuePeriodMutation() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (body: DuePeriodPayload) => createDuePeriod(body),
    onSuccess: () => void qc.invalidateQueries({ queryKey: ['due-periods'] }),
  })
}

export function useUpdateDuePeriodMutation() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: ({ id, body }: { id: number; body: Partial<DuePeriodPayload> }) =>
      updateDuePeriod(id, body),
    onSuccess: (_d, { id }) => {
      void qc.invalidateQueries({ queryKey: ['due-periods'] })
      void qc.invalidateQueries({ queryKey: duePeriodDetailQueryKey(id) })
    },
  })
}

export function useDeleteDuePeriodMutation() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (id: number) => deleteDuePeriod(id),
    onSuccess: (_d, id) => {
      void qc.invalidateQueries({ queryKey: ['due-periods'] })
      void qc.removeQueries({ queryKey: duePeriodDetailQueryKey(id) })
    },
  })
}

export function useCloseDuePeriodMutation() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (id: number) => closeDuePeriod(id),
    onSuccess: (_d, id) => {
      void qc.invalidateQueries({ queryKey: ['due-periods'] })
      void qc.invalidateQueries({ queryKey: duePeriodDetailQueryKey(id) })
    },
  })
}

export function useLockDuePeriodMutation() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (id: number) => lockDuePeriod(id),
    onSuccess: (_d, id) => {
      void qc.invalidateQueries({ queryKey: ['due-periods'] })
      void qc.invalidateQueries({ queryKey: duePeriodDetailQueryKey(id) })
    },
  })
}
