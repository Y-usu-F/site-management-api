export interface DashboardAnalytics {
  finance: {
    due_total: number
    paid_total: number
    unpaid_total: number
    payment_count: number
  }
  operations: {
    open_service_requests: number
    active_work_orders: number
    upcoming_reservations: number
  }
  residents: {
    resident_count: number
    active_occupancy_count: number
    unit_count: number
  }
  trends: {
    payments_last_30_days: Array<{
      date: string
      total: number
    }>
    service_requests_last_30_days: Array<{
      date: string
      count: number
    }>
  }
  distributions: {
    service_request_statuses: Array<{
      status: string
      count: number
    }>
    work_order_statuses: Array<{
      status: string
      count: number
    }>
  }
}
