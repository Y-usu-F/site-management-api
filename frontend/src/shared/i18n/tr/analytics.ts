const analytics = {
  title: 'Analitik',
  trends: {
    payments: 'Odeme trendi',
    serviceRequests: 'Servis talebi trendi',
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
    last7Days: 'Son 7 gun',
    last30Days: 'Son 30 gun',
    last90Days: 'Son 90 gun',
  },
} as const

export default analytics
