import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'

import {
  getNotificationRecipient,
  listNotificationRecipients,
  markNotificationRecipientRead,
} from '@/features/communication/notification.api'

export function useNotificationRecipientsQuery(
  params: Record<string, unknown>,
  enabled = true,
  pollingMs?: number,
) {
  return useQuery({
    queryKey: ['notification-recipients', params],
    queryFn: () => listNotificationRecipients(params as never),
    enabled,
    refetchInterval: enabled && pollingMs ? pollingMs : false,
  })
}

export function useNotificationRecipientQuery(id: number, enabled = true) {
  return useQuery({
    queryKey: ['notification-recipients', 'detail', id],
    queryFn: () => getNotificationRecipient(id),
    enabled,
  })
}

export function useMarkNotificationRecipientReadMutation() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (id: number) => markNotificationRecipientRead(id),
    onSuccess: (_d, id) => {
      void qc.invalidateQueries({ queryKey: ['notification-recipients'] })
      void qc.invalidateQueries({ queryKey: ['notification-recipients', 'detail', id] })
    },
  })
}

