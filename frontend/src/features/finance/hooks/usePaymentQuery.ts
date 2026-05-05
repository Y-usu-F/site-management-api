import { useQuery } from '@tanstack/react-query'

import { getPayment } from '@/features/finance/api/paymentApi'

export function paymentDetailQueryKey(id: number) {
  return ['payments', 'detail', id] as const
}

export function usePaymentQuery(id: number, enabled = true) {
  return useQuery({
    queryKey: paymentDetailQueryKey(id),
    queryFn: () => getPayment(id),
    enabled,
  })
}
