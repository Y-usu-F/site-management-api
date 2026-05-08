import i18n from 'i18next'
import { initReactI18next } from 'react-i18next'

import common from '@/shared/i18n/tr/common'
import auth from '@/shared/i18n/tr/auth'
import finance from '@/shared/i18n/tr/finance'
import navigation from '@/shared/i18n/tr/navigation'
import notifications from '@/shared/i18n/tr/notifications'
import operations from '@/shared/i18n/tr/operations'
import residents from '@/shared/i18n/tr/residents'
import analytics from '@/shared/i18n/tr/analytics'
import site from '@/shared/i18n/tr/site'

function withNamespaceAlias<T extends Record<string, unknown>>(namespace: string, resource: T): T & Record<string, T> {
  return {
    ...resource,
    [namespace]: resource,
  }
}

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
        common: withNamespaceAlias('common', common),
        auth: withNamespaceAlias('auth', auth),
        navigation: withNamespaceAlias('navigation', navigation),
        finance: withNamespaceAlias('finance', finance),
        operations: withNamespaceAlias('operations', operations),
        residents: withNamespaceAlias('residents', residents),
        analytics: withNamespaceAlias('analytics', analytics),
        notifications: withNamespaceAlias('notifications', notifications),
        site: withNamespaceAlias('site', site),
      },
    },
  })
}

export { i18n }
