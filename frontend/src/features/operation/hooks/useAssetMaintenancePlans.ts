import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'

import {
  createAssetMaintenancePlan,
  getAssetMaintenancePlan,
  listAssetMaintenancePlans,
  updateAssetMaintenancePlan,
} from '@/features/operation/api/assetMaintenancePlanApi'

export function useAssetMaintenancePlansQuery(params: Record<string, unknown>, enabled = true) {
  return useQuery({
    queryKey: ['asset-maintenance-plans', params],
    queryFn: () => listAssetMaintenancePlans(params),
    enabled,
  })
}

export function useAssetMaintenancePlanQuery(id: number, enabled = true) {
  return useQuery({
    queryKey: ['asset-maintenance-plans', 'detail', id],
    queryFn: () => getAssetMaintenancePlan(id),
    enabled,
  })
}

export function useCreateAssetMaintenancePlanMutation() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (body: Record<string, unknown>) => createAssetMaintenancePlan(body),
    onSuccess: () => void qc.invalidateQueries({ queryKey: ['asset-maintenance-plans'] }),
  })
}

export function useUpdateAssetMaintenancePlanMutation() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: ({ id, body }: { id: number; body: Record<string, unknown> }) =>
      updateAssetMaintenancePlan(id, body),
    onSuccess: (_d, { id }) => {
      void qc.invalidateQueries({ queryKey: ['asset-maintenance-plans'] })
      void qc.invalidateQueries({ queryKey: ['asset-maintenance-plans', 'detail', id] })
    },
  })
}
