const analytics = {
  title: 'Analitik',
  trends: {
    payments: 'Ödeme Trendi',
    serviceRequests: 'Servis Talebi Trendi',
  },
  distributions: {
    serviceRequests: 'Servis talebi durum dagilimi',
    workOrders: 'Is emri durum dagilimi',
  },
  axes: {
    date: 'Tarih',
    total: 'Toplam',
    count: 'Adet',
    status: 'Durum',
  },
  range: {
    last7Days: 'Son 7 gün',
    last30Days: 'Son 30 gün',
    last90Days: 'Son 90 gün',
  },
} as const

export default analytics
