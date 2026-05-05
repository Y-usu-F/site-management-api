import { useMutation, useQueryClient } from '@tanstack/react-query'

import { cancelPayment, createManualPayment } from '@/features/finance/api/paymentApi'
import { paymentDetailQueryKey } from '@/features/finance/hooks/usePaymentQuery'
import type { PaymentCreatePayload } from '@/features/finance/types'

export function useCreateManualPaymentMutation() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (body: PaymentCreatePayload) => createManualPayment(body),
    onSuccess: () => void qc.invalidateQueries({ queryKey: ['payments'] }),
  })
}

export function useCancelPaymentMutation() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (id: number) => cancelPayment(id),
    onSuccess: (_d, id) => {
      void qc.invalidateQueries({ queryKey: ['payments'] })
      void qc.invalidateQueries({ queryKey: paymentDetailQueryKey(id) })
    },
  })
}
