import { apiRequest } from '@/shared/api/client'
import { buildQueryString } from '@/shared/lib/buildQueryString'

import type { DashboardAnalytics } from '@/features/analytics/types'

export type AnalyticsRange = '7d' | '30d' | '90d'

export async function getDashboardAnalytics(range: AnalyticsRange = '30d'): Promise<DashboardAnalytics> {
  const qs = buildQueryString({ range })
  return apiRequest<DashboardAnalytics>(`/analytics/dashboard${qs}`)
}
