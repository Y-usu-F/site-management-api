import { apiRequest } from '@/shared/api/client'
import { buildQueryString } from '@/shared/lib/buildQueryString'

import type {
  NotificationRecipient,
  NotificationRecipientListResponse,
  NotificationUnreadCount,
} from '@/features/communication/notification.types'

export async function listNotificationRecipients(params?: {
  page?: number
  per_page?: number
  sort?: string
  direction?: 'asc' | 'desc'
  status?: string
  message_id?: number
  read_status?: 'unread' | 'read'
}): Promise<NotificationRecipientListResponse> {
  const qs = buildQueryString({
    page: params?.page,
    per_page: params?.per_page,
    sort: params?.sort,
    direction: params?.direction,
    status: params?.status,
    message_id: params?.message_id,
    read_status: params?.read_status,
  })
  return apiRequest<NotificationRecipientListResponse>(`/notification-recipients${qs}`)
}

export async function getNotificationRecipient(id: number): Promise<NotificationRecipient> {
  return apiRequest<NotificationRecipient>(`/notification-recipients/${id}`)
}

export async function markNotificationRecipientRead(id: number): Promise<NotificationRecipient> {
  return apiRequest<NotificationRecipient>(`/notification-recipients/${id}/mark-read`, {
    method: 'POST',
  })
}

export async function getNotificationUnreadCount(): Promise<NotificationUnreadCount> {
  return apiRequest<NotificationUnreadCount>('/notification-recipients/unread-count')
}

export async function markAllNotificationRecipientsRead(): Promise<{ marked_count: number }> {
  return apiRequest<{ marked_count: number }>('/notification-recipients/mark-all-read', {
    method: 'POST',
  })
}

