import { PlaceholderPage } from '@/shared/components/PlaceholderPage'
import { useTranslation } from 'react-i18next'

export function RolesPage() {
  const { t } = useTranslation(['navigation'])
  return (
    <PlaceholderPage
      title={t('navigation.roles')}
      subtitle={t('navigation.rolesSubtitle')}
    />
  )
}
