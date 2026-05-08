import { PlaceholderPage } from '@/shared/components/PlaceholderPage'
import { useTranslation } from 'react-i18next'

export function DueItemsPage() {
  const { t } = useTranslation(['finance'])
  return (
    <PlaceholderPage
      title={t('dueItems', { ns: 'finance' })}
      subtitle={t('widgets.dueItemsDescription', { ns: 'finance' })}
    />
  )
}
