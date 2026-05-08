import { PlaceholderPage } from '@/shared/components/PlaceholderPage'
import { useTranslation } from 'react-i18next'

export function ServiceRequestsPage() {
  const { t } = useTranslation(['operations'])
  return (
    <PlaceholderPage
      title={t('operations.common.serviceRequests')}
      subtitle={t('operations.widgets.openServiceRequestsDescription')}
    />
  )
}
