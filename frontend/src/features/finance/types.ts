import type { PaginatedResponse } from '@/shared/types/pagination'

export interface DueDefinition {
  id: number
  site_id?: number | null
  block_id?: number | null
  name: string
  code?: string | null
  calculation_type: string
  amount: number | string
  currency: string
  status: string
  created_at?: string | null
  updated_at?: string | null
}

export interface DueDefinitionPayload {
  site_id?: number | null
  block_id?: number | null
  name: string
  code?: string | null
  calculation_type: string
  amount: number
  currency?: string
  status?: string
}

export interface DuePeriod {
  id: number
  site_id: number
  period_key: string
  start_date: string
  end_date: string
  due_date: string
  status: string
  created_at?: string | null
  updated_at?: string | null
}

export interface DuePeriodPayload {
  site_id: number
  period_key: string
  start_date: string
  end_date: string
  due_date: string
  status?: string
}

export interface DueItem {
  id: number
  site_id: number
  unit_id: number
  due_period_id: number
  due_definition_id: number
  amount: number | string
  interest_amount?: number | string | null
  penalty_amount?: number | string | null
  paid_amount: number | string
  remaining_amount: number | string
  currency: string
  due_date: string
  status: string
  description?: string | null
  created_at?: string | null
  updated_at?: string | null
}

export interface Payment {
  id: number
  site_id: number
  unit_id?: number | null
  resident_profile_id?: number | null
  payment_no: string
  amount: number | string
  allocated_amount?: number | string | null
  currency: string
  payment_date: string
  method: string
  status: string
  provider?: string | null
  provider_reference?: string | null
  description?: string | null
  created_at?: string | null
  updated_at?: string | null
}

export interface PaymentCreatePayload {
  site_id: number
  unit_id?: number | null
  resident_profile_id?: number | null
  amount: number
  currency?: string
  payment_date?: string
  method: string
  description?: string | null
}

export interface Deposit {
  id: number
  site_id: number
  unit_id: number
  resident_profile_id: number
  deposit_no: string
  initial_amount: number | string
  balance_amount: number | string
  currency: string
  status: string
  received_at?: string | null
  notes?: string | null
  created_at?: string | null
  updated_at?: string | null
}

export interface DepositPayload {
  site_id: number
  unit_id: number
  resident_profile_id: number
  initial_amount: number
  currency?: string
  notes?: string | null
}

export interface DepositTransaction {
  id: number
  deposit_id: number
  transaction_type: string
  amount: number | string
  currency: string
  due_item_id?: number | null
  payment_id?: number | null
  description?: string | null
  transaction_date: string
}

export interface LookupResident {
  id: number
  first_name?: string | null
  last_name?: string | null
}

export interface LookupUnit {
  id: number
  unit_no?: string | null
}

export type DueDefinitionListResponse = PaginatedResponse<DueDefinition>
export type DuePeriodListResponse = PaginatedResponse<DuePeriod>
export type DueItemListResponse = PaginatedResponse<DueItem>
export type PaymentListResponse = PaginatedResponse<Payment>
export type DepositListResponse = PaginatedResponse<Deposit>
export type DepositTransactionListResponse = PaginatedResponse<DepositTransaction>
