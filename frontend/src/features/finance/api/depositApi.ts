import { apiRequest } from '@/shared/api/client'
import { buildQueryString } from '@/shared/lib/buildQueryString'

import type {
  Deposit,
  DepositListResponse,
  DepositPayload,
  DepositTransactionListResponse,
} from '@/features/finance/types'

export async function listDeposits(params?: {
  page?: number
  per_page?: number
  status?: string
  unit_id?: number
  resident_profile_id?: number
  site_id?: number
}): Promise<DepositListResponse> {
  const qs = buildQueryString({
    page: params?.page,
    per_page: params?.per_page,
    status: params?.status,
    unit_id: params?.unit_id,
    resident_profile_id: params?.resident_profile_id,
    site_id: params?.site_id,
  })
  return apiRequest<DepositListResponse>(`/deposits${qs}`)
}

export async function getDeposit(id: number): Promise<Deposit> {
  return apiRequest<Deposit>(`/deposits/${id}`)
}

export async function createDeposit(body: DepositPayload): Promise<Deposit> {
  return apiRequest<Deposit>('/deposits', {
    method: 'POST',
    body: JSON.stringify(body),
  })
}

export async function updateDeposit(
  id: number,
  body: Partial<DepositPayload>,
): Promise<Deposit> {
  return apiRequest<Deposit>(`/deposits/${id}`, {
    method: 'PUT',
    body: JSON.stringify(body),
  })
}

export async function listDepositTransactions(
  depositId: number,
): Promise<DepositTransactionListResponse> {
  return apiRequest<DepositTransactionListResponse>(`/deposits/${depositId}/transactions`)
}

export async function receiveDeposit(
  id: number,
  body?: { transaction_date?: string; description?: string },
): Promise<Deposit> {
  return apiRequest<Deposit>(`/deposits/${id}/receive`, {
    method: 'POST',
    body: JSON.stringify(body ?? {}),
  })
}

export async function refundDeposit(
  id: number,
  body: { amount: number; transaction_date?: string; description?: string },
): Promise<Deposit> {
  return apiRequest<Deposit>(`/deposits/${id}/refund`, {
    method: 'POST',
    body: JSON.stringify(body),
  })
}

export async function deductDeposit(
  id: number,
  body: { amount: number; transaction_date?: string; description?: string },
): Promise<Deposit> {
  return apiRequest<Deposit>(`/deposits/${id}/deduct`, {
    method: 'POST',
    body: JSON.stringify(body),
  })
}

export async function applyDepositToDebt(
  id: number,
  body: {
    due_item_id: number
    amount: number
    transaction_date?: string
    description?: string
  },
): Promise<Deposit> {
  return apiRequest<Deposit>(`/deposits/${id}/apply-to-debt`, {
    method: 'POST',
    body: JSON.stringify(body),
  })
}

export async function cancelDeposit(
  id: number,
  body?: { transaction_date?: string; description?: string },
): Promise<Deposit> {
  return apiRequest<Deposit>(`/deposits/${id}/cancel`, {
    method: 'POST',
    body: JSON.stringify(body ?? {}),
  })
}
