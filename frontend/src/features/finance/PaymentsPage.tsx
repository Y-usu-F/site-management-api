import { PlaceholderPage } from '@/shared/components/PlaceholderPage'
import { useTranslation } from 'react-i18next'

export function PaymentsPage() {
  const { t } = useTranslation(['finance'])
  return (
    <PlaceholderPage
      title={t('payments', { ns: 'finance' })}
      subtitle={t('widgets.paymentsDescription', { ns: 'finance' })}
    />
  )
}
