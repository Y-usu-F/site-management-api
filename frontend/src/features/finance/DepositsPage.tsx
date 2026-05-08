import { PlaceholderPage } from '@/shared/components/PlaceholderPage'
import { useTranslation } from 'react-i18next'

export function DepositsPage() {
  const { t } = useTranslation(['finance'])
  return (
    <PlaceholderPage
      title={t('finance.common.deposits')}
      subtitle={t('finance.widgets.depositsDescription')}
    />
  )
}
