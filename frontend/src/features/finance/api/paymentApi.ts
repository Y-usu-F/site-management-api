import { apiRequest } from '@/shared/api/client'
import { buildQueryString } from '@/shared/lib/buildQueryString'

import type { Payment, PaymentCreatePayload, PaymentListResponse } from '@/features/finance/types'

export async function listPayments(params?: {
  page?: number
  per_page?: number
  status?: string
  unit_id?: number
  resident_profile_id?: number
  site_id?: number
}): Promise<PaymentListResponse> {
  const qs = buildQueryString({
    page: params?.page,
    per_page: params?.per_page,
    status: params?.status,
    unit_id: params?.unit_id,
    resident_profile_id: params?.resident_profile_id,
    site_id: params?.site_id,
  })
  return apiRequest<PaymentListResponse>(`/payments${qs}`)
}

export async function getPayment(id: number): Promise<Payment> {
  return apiRequest<Payment>(`/payments/${id}`)
}

export async function createManualPayment(body: PaymentCreatePayload): Promise<Payment> {
  return apiRequest<Payment>('/payments/manual', {
    method: 'POST',
    body: JSON.stringify(body),
  })
}

export async function cancelPayment(id: number): Promise<Payment> {
  return apiRequest<Payment>(`/payments/${id}/cancel`, {
    method: 'POST',
    body: JSON.stringify({}),
  })
}
