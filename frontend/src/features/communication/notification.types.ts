import type { PaginatedResponse } from '@/shared/types/pagination'

export interface NotificationRecipient {
  id: number
  message_id: number
  user_id?: number | null
  resident_profile_id?: number | null
  email?: string | null
  phone?: string | null
  status?: string | null
  read_at?: string | null
  created_at?: string | null
}

export type NotificationRecipientListResponse = PaginatedResponse<NotificationRecipient>

