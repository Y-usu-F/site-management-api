import i18n from 'i18next'
import { initReactI18next } from 'react-i18next'

import common from '@/shared/i18n/tr/common'
import finance from '@/shared/i18n/tr/finance'
import navigation from '@/shared/i18n/tr/navigation'
import notifications from '@/shared/i18n/tr/notifications'
import operations from '@/shared/i18n/tr/operations'
import residents from '@/shared/i18n/tr/residents'
import analytics from '@/shared/i18n/tr/analytics'

if (!i18n.isInitialized) {
  void i18n.use(initReactI18next).init({
    lng: 'tr',
    fallbackLng: 'tr',
    debug: false,
    interpolation: {
      escapeValue: false,
    },
    resources: {
      tr: {
        common,
        navigation,
        finance,
        operations,
        residents,
        analytics,
        notifications,
      },
    },
  })
}

export { i18n }
