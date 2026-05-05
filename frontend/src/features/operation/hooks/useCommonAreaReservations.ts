import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'

import {
  createCommonAreaReservation,
  getCommonAreaReservation,
  listCommonAreaReservations,
  updateCommonAreaReservation,
} from '@/features/operation/api/commonAreaReservationApi'

export function useCommonAreaReservationsQuery(params: Record<string, unknown>, enabled = true) {
  return useQuery({
    queryKey: ['common-area-reservations', params],
    queryFn: () => listCommonAreaReservations(params),
    enabled,
  })
}

export function useCommonAreaReservationQuery(id: number, enabled = true) {
  return useQuery({
    queryKey: ['common-area-reservations', 'detail', id],
    queryFn: () => getCommonAreaReservation(id),
    enabled,
  })
}

export function useCreateCommonAreaReservationMutation() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (body: Record<string, unknown>) => createCommonAreaReservation(body),
    onSuccess: () => void qc.invalidateQueries({ queryKey: ['common-area-reservations'] }),
  })
}

export function useUpdateCommonAreaReservationMutation() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: ({ id, body }: { id: number; body: Record<string, unknown> }) =>
      updateCommonAreaReservation(id, body),
    onSuccess: (_d, { id }) => {
      void qc.invalidateQueries({ queryKey: ['common-area-reservations'] })
      void qc.invalidateQueries({ queryKey: ['common-area-reservations', 'detail', id] })
    },
  })
}
