import { useQuery } from '@tanstack/react-query'

import { listPayments } from '@/features/finance/api/paymentApi'

export function usePaymentsQuery(
  params: {
    page?: number
    per_page?: number
    status?: string
    unit_id?: number
    resident_profile_id?: number
    site_id?: number
  },
  enabled = true,
) {
  return useQuery({
    queryKey: ['payments', params],
    queryFn: () => listPayments(params),
    enabled,
  })
}
