import { apiRequest } from '@/shared/api/client'

import type { DashboardAnalytics } from '@/features/analytics/types'

export async function getDashboardAnalytics(): Promise<DashboardAnalytics> {
  return apiRequest<DashboardAnalytics>('/analytics/dashboard')
}
