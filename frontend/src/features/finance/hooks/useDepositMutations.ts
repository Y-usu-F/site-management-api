import { useMutation, useQueryClient } from '@tanstack/react-query'

import {
  applyDepositToDebt,
  cancelDeposit,
  createDeposit,
  deductDeposit,
  receiveDeposit,
  refundDeposit,
  updateDeposit,
} from '@/features/finance/api/depositApi'
import { depositDetailQueryKey } from '@/features/finance/hooks/useDepositQuery'
import type { DepositPayload } from '@/features/finance/types'

export function useCreateDepositMutation() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (body: DepositPayload) => createDeposit(body),
    onSuccess: () => void qc.invalidateQueries({ queryKey: ['deposits'] }),
  })
}

export function useUpdateDepositMutation() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: ({ id, body }: { id: number; body: Partial<DepositPayload> }) =>
      updateDeposit(id, body),
    onSuccess: (_d, { id }) => {
      void qc.invalidateQueries({ queryKey: ['deposits'] })
      void qc.invalidateQueries({ queryKey: depositDetailQueryKey(id) })
    },
  })
}

export function useReceiveDepositMutation() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (id: number) => receiveDeposit(id),
    onSuccess: (_d, id) => {
      void qc.invalidateQueries({ queryKey: ['deposits'] })
      void qc.invalidateQueries({ queryKey: depositDetailQueryKey(id) })
      void qc.invalidateQueries({ queryKey: ['deposits', 'transactions', id] })
    },
  })
}

export function useRefundDepositMutation() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: ({ id, amount }: { id: number; amount: number }) => refundDeposit(id, { amount }),
    onSuccess: (_d, { id }) => {
      void qc.invalidateQueries({ queryKey: ['deposits'] })
      void qc.invalidateQueries({ queryKey: depositDetailQueryKey(id) })
      void qc.invalidateQueries({ queryKey: ['deposits', 'transactions', id] })
    },
  })
}

export function useDeductDepositMutation() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: ({ id, amount }: { id: number; amount: number }) => deductDeposit(id, { amount }),
    onSuccess: (_d, { id }) => {
      void qc.invalidateQueries({ queryKey: ['deposits'] })
      void qc.invalidateQueries({ queryKey: depositDetailQueryKey(id) })
      void qc.invalidateQueries({ queryKey: ['deposits', 'transactions', id] })
    },
  })
}

export function useApplyToDebtDepositMutation() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: ({ id, due_item_id, amount }: { id: number; due_item_id: number; amount: number }) =>
      applyDepositToDebt(id, { due_item_id, amount }),
    onSuccess: (_d, { id }) => {
      void qc.invalidateQueries({ queryKey: ['deposits'] })
      void qc.invalidateQueries({ queryKey: depositDetailQueryKey(id) })
      void qc.invalidateQueries({ queryKey: ['deposits', 'transactions', id] })
      void qc.invalidateQueries({ queryKey: ['due-items'] })
    },
  })
}

export function useCancelDepositMutation() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (id: number) => cancelDeposit(id),
    onSuccess: (_d, id) => {
      void qc.invalidateQueries({ queryKey: ['deposits'] })
      void qc.invalidateQueries({ queryKey: depositDetailQueryKey(id) })
      void qc.invalidateQueries({ queryKey: ['deposits', 'transactions', id] })
    },
  })
}
