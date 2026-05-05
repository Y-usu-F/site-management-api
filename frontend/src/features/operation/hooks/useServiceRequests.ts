import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'

import {
  createServiceRequest,
  getServiceRequest,
  listServiceRequests,
  updateServiceRequest,
} from '@/features/operation/api/serviceRequestApi'

export function useServiceRequestsQuery(params: Record<string, unknown>, enabled = true) {
  return useQuery({
    queryKey: ['service-requests', params],
    queryFn: () => listServiceRequests(params),
    enabled,
  })
}

export function useServiceRequestQuery(id: number, enabled = true) {
  return useQuery({
    queryKey: ['service-requests', 'detail', id],
    queryFn: () => getServiceRequest(id),
    enabled,
  })
}

export function useCreateServiceRequestMutation() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (body: Record<string, unknown>) => createServiceRequest(body),
    onSuccess: () => void qc.invalidateQueries({ queryKey: ['service-requests'] }),
  })
}

export function useUpdateServiceRequestMutation() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: ({ id, body }: { id: number; body: Record<string, unknown> }) =>
      updateServiceRequest(id, body),
    onSuccess: (_d, { id }) => {
      void qc.invalidateQueries({ queryKey: ['service-requests'] })
      void qc.invalidateQueries({ queryKey: ['service-requests', 'detail', id] })
    },
  })
}
