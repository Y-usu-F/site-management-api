export type OperationActionName = 'approve' | 'reject' | 'cancel' | 'start' | 'complete'

export type OperationActionEntity =
  | 'service_request'
  | 'work_order'
  | 'common_area_reservation'
  | 'asset_maintenance_plan'
  | 'asset_maintenance_record'

interface OperationActionConfigItem {
  permission: string
  method: 'POST'
  path: (id: number) => string
  confirmText: string
}

type OperationActionConfig = Partial<Record<OperationActionName, OperationActionConfigItem>>

export const operationActionConfig: Record<OperationActionEntity, OperationActionConfig> = {
  service_request: {
    cancel: {
      permission: 'service_request.cancel',
      method: 'POST',
      path: (id) => `/service-requests/${id}/cancel`,
      confirmText: 'Service request iptal edilsin mi?',
    },
  },
  work_order: {
    start: {
      permission: 'work_order.start',
      method: 'POST',
      path: (id) => `/work-orders/${id}/start`,
      confirmText: 'Work order baslatilsin mi?',
    },
    complete: {
      permission: 'work_order.complete',
      method: 'POST',
      path: (id) => `/work-orders/${id}/complete`,
      confirmText: 'Work order tamamlandi olarak isaretlensin mi?',
    },
    cancel: {
      permission: 'work_order.cancel',
      method: 'POST',
      path: (id) => `/work-orders/${id}/cancel`,
      confirmText: 'Work order iptal edilsin mi?',
    },
  },
  common_area_reservation: {
    approve: {
      permission: 'common_area_reservation.approve',
      method: 'POST',
      path: (id) => `/common-area-reservations/${id}/approve`,
      confirmText: 'Rezervasyon onaylansin mi?',
    },
    reject: {
      permission: 'common_area_reservation.reject',
      method: 'POST',
      path: (id) => `/common-area-reservations/${id}/reject`,
      confirmText: 'Rezervasyon reddedilsin mi?',
    },
    cancel: {
      permission: 'common_area_reservation.cancel',
      method: 'POST',
      path: (id) => `/common-area-reservations/${id}/cancel`,
      confirmText: 'Rezervasyon iptal edilsin mi?',
    },
    complete: {
      permission: 'common_area_reservation.complete',
      method: 'POST',
      path: (id) => `/common-area-reservations/${id}/complete`,
      confirmText: 'Rezervasyon tamamlandi olarak isaretlensin mi?',
    },
  },
  asset_maintenance_plan: {
    cancel: {
      permission: 'asset_maintenance_plan.cancel',
      method: 'POST',
      path: (id) => `/asset-maintenance-plans/${id}/cancel`,
      confirmText: 'Bakim plani iptal edilsin mi?',
    },
  },
  asset_maintenance_record: {
    cancel: {
      permission: 'asset_maintenance_record.cancel',
      method: 'POST',
      path: (id) => `/asset-maintenance-records/${id}/cancel`,
      confirmText: 'Bakim kaydi iptal edilsin mi?',
    },
  },
}

