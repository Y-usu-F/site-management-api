import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'

import {
  createWorkOrder,
  getWorkOrder,
  listWorkOrders,
  updateWorkOrder,
} from '@/features/operation/api/workOrderApi'

export function useWorkOrdersQuery(params: Record<string, unknown>, enabled = true) {
  return useQuery({
    queryKey: ['work-orders', params],
    queryFn: () => listWorkOrders(params),
    enabled,
  })
}

export function useWorkOrderQuery(id: number, enabled = true) {
  return useQuery({
    queryKey: ['work-orders', 'detail', id],
    queryFn: () => getWorkOrder(id),
    enabled,
  })
}

export function useCreateWorkOrderMutation() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (body: Record<string, unknown>) => createWorkOrder(body),
    onSuccess: () => void qc.invalidateQueries({ queryKey: ['work-orders'] }),
  })
}

export function useUpdateWorkOrderMutation() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: ({ id, body }: { id: number; body: Record<string, unknown> }) =>
      updateWorkOrder(id, body),
    onSuccess: (_d, { id }) => {
      void qc.invalidateQueries({ queryKey: ['work-orders'] })
      void qc.invalidateQueries({ queryKey: ['work-orders', 'detail', id] })
    },
  })
}
