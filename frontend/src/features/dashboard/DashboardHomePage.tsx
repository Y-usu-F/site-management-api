import { PlaceholderPage } from '@/shared/components/PlaceholderPage'
import { useTranslation } from 'react-i18next'

export function DashboardHomePage() {
  const { t } = useTranslation(['navigation'])

  return (
    <PlaceholderPage
      title={t('navigation.dashboard')}
      subtitle={t('navigation.overview')}
    />
  )
}
