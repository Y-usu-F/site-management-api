const analytics = {
  title: 'Analitik',
  trends: {
    payments: 'Son 30 gun odeme trendi',
    serviceRequests: 'Son 30 gun servis talebi trendi',
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
} as const

export default analytics
