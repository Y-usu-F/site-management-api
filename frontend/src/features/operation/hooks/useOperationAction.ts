import { useMutation, useQueryClient } from '@tanstack/react-query'

import {
  operationActionConfig,
  type OperationActionEntity,
  type OperationActionName,
} from '@/features/operation/actions/config'
import { apiRequest } from '@/shared/api/client'
import { useEffectiveCan } from '@/shared/hooks/useEffectiveCan'

const ENTITY_QUERY_KEY: Record<OperationActionEntity, string> = {
  service_request: 'service-requests',
  work_order: 'work-orders',
  common_area_reservation: 'common-area-reservations',
  asset_maintenance_plan: 'asset-maintenance-plans',
  asset_maintenance_record: 'asset-maintenance-records',
}

export function useOperationAction(entity: OperationActionEntity, action: OperationActionName) {
  const qc = useQueryClient()
  const actionConfig = operationActionConfig[entity][action]
  const canRun = useEffectiveCan(actionConfig?.permission ?? '')

  const mutation = useMutation({
    mutationFn: async (id: number) => {
      if (!actionConfig) {
        throw new Error(`Action "${action}" for "${entity}" desteklenmiyor.`)
      }
      return apiRequest(actionConfig.path(id), { method: actionConfig.method, body: JSON.stringify({}) })
    },
    onSuccess: (_data, id) => {
      const key = ENTITY_QUERY_KEY[entity]
      void qc.invalidateQueries({ queryKey: [key] })
      void qc.invalidateQueries({ queryKey: [key, 'detail', id] })
    },
  })

  return {
    actionConfig,
    canRun: Boolean(actionConfig) && canRun,
    run: mutation.mutate,
    runAsync: mutation.mutateAsync,
    isPending: mutation.isPending,
    error: mutation.error,
  }
}

