import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'

import {
  createAssetMaintenanceRecord,
  getAssetMaintenanceRecord,
  listAssetMaintenanceRecords,
} from '@/features/operation/api/assetMaintenanceRecordApi'

export function useAssetMaintenanceRecordsQuery(params: Record<string, unknown>, enabled = true) {
  return useQuery({
    queryKey: ['asset-maintenance-records', params],
    queryFn: () => listAssetMaintenanceRecords(params),
    enabled,
  })
}

export function useAssetMaintenanceRecordQuery(id: number, enabled = true) {
  return useQuery({
    queryKey: ['asset-maintenance-records', 'detail', id],
    queryFn: () => getAssetMaintenanceRecord(id),
    enabled,
  })
}

export function useCreateAssetMaintenanceRecordMutation() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (body: Record<string, unknown>) => createAssetMaintenanceRecord(body),
    onSuccess: () => void qc.invalidateQueries({ queryKey: ['asset-maintenance-records'] }),
  })
}
