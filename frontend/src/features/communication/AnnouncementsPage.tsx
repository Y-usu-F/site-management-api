import { PlaceholderPage } from '@/shared/components/PlaceholderPage'
import { useTranslation } from 'react-i18next'

export function AnnouncementsPage() {
  const { t } = useTranslation(['notifications'])
  return (
    <PlaceholderPage
      title={t('notifications.announcementsTitle')}
      subtitle={t('notifications.announcementsSubtitle')}
    />
  )
}
