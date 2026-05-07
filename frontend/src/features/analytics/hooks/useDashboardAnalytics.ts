import { useQuery } from '@tanstack/react-query'

import { getDashboardAnalytics } from '@/features/analytics/api/dashboardAnalyticsApi'
import type { AnalyticsRange } from '@/features/analytics/api/dashboardAnalyticsApi'

export function useDashboardAnalyticsQuery(range: AnalyticsRange = '30d', enabled = true) {
  return useQuery({
    queryKey: ['analytics', 'dashboard', range],
    queryFn: () => getDashboardAnalytics(range),
    enabled,
  })
}
