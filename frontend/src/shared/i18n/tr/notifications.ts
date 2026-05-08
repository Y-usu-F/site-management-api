const notifications = {
  title: 'Bildirimler',
  announcementsTitle: 'Duyurular',
  announcementsSubtitle: 'Site duyurulari ve okunma durumlari.',
  notificationLabel: 'Bildirim',
  listFailed: 'Bildirimler alinamadi.',
  emptyTitle: 'Bildirim bulunamadi',
  emptyDescription: 'Henuz bir bildirim yok.',
  markedRead: 'Okundu olarak isaretlendi',
  markedAllRead: '{{count}} bildirim okundu olarak isaretlendi',
  unreadOnPage: 'Sayfadaki okunmamis',
  table: {
    id: 'ID',
    message: 'Mesaj',
    status: 'Durum',
    read: 'Okunma',
    actions: 'Islemler',
  },
} as const

export default notifications
