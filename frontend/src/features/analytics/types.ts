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
}
