import { useQuery } from '@tanstack/react-query'

import { getDeposit, listDepositTransactions } from '@/features/finance/api/depositApi'

export function depositDetailQueryKey(id: number) {
  return ['deposits', 'detail', id] as const
}

export function useDepositQuery(id: number, enabled = true) {
  return useQuery({
    queryKey: depositDetailQueryKey(id),
    queryFn: () => getDeposit(id),
    enabled,
  })
}

export function useDepositTransactionsQuery(depositId: number, enabled = true) {
  return useQuery({
    queryKey: ['deposits', 'transactions', depositId],
    queryFn: () => listDepositTransactions(depositId),
    enabled,
  })
}
