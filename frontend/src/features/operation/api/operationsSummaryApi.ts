import { apiRequest } from '@/shared/api/client'

import type { OperationsSummary } from '@/features/operation/types'

export async function getOperationsSummary(): Promise<OperationsSummary> {
  return apiRequest<OperationsSummary>('/operations/summary')
}

