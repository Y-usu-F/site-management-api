import { useQuery } from '@tanstack/react-query'

import { getDashboardAnalytics } from '@/features/analytics/api/dashboardAnalyticsApi'

export function useDashboardAnalyticsQuery(enabled = true) {
  return useQuery({
    queryKey: ['analytics', 'dashboard'],
    queryFn: getDashboardAnalytics,
    enabled,
  })
}
