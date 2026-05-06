const operations = {
  title: 'Operasyon',
  widgets: {
    openServiceRequestsTitle: 'Acik servis talepleri',
    openServiceRequestsDescription: 'Acil/aktif is talepleri',
    activeWorkOrdersTitle: 'Aktif is emirleri',
    activeWorkOrdersDescription: 'Devam eden is emirleri',
    upcomingReservationsTitle: 'Yaklasan rezervasyonlar',
    upcomingReservationsDescription: 'Bekleyen + onayli rezervasyonlar',
    activeMaintenancePlansTitle: 'Aktif bakim planlari',
    activeMaintenancePlansDescription: 'Etkin bakim planlari',
  },
  cards: {
    serviceRequests: 'Servis talepleri',
    workOrders: 'Is emirleri',
    commonAreas: 'Ortak alanlar',
    reservations: 'Rezervasyonlar',
    assets: 'Varliklar',
    maintenancePlans: 'Bakim planlari',
    maintenanceRecords: 'Bakim kayitlari',
  },
  summary: {
    permissionRequired: 'Ozeti goruntulemek icin service_request.list yetkisi gerekli',
    loading: 'Yukleniyor...',
    failed: 'Ozet alinamadi',
  },
} as const

export default operations
