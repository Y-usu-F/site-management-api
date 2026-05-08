import { useTranslation } from 'react-i18next'

interface PermissionDeniedNoticeProps {
  permission: string
  title?: string
}

export function PermissionDeniedNotice({
  permission,
  title,
}: PermissionDeniedNoticeProps) {
  const { t } = useTranslation(['common'])
  const resolvedTitle = title || t('common.permission.requiredTitle')
  return (
    <div className="rounded-xl border border-amber-200 bg-amber-50 p-6 dark:border-amber-900 dark:bg-amber-950/40">
      <h1 className="text-lg font-semibold text-amber-900 dark:text-amber-100">{resolvedTitle}</h1>
      <p className="mt-2 text-sm text-amber-800 dark:text-amber-200">
        {t('common.permission.youNeedPrefix')}
        <code className="rounded bg-amber-100 px-1 dark:bg-amber-900">{permission}</code>
        {t('common.permission.youNeedSuffix')}
        <code className="rounded bg-amber-100 px-1 dark:bg-amber-900">VITE_STRICT_PERMISSIONS=false</code>
        {t('common.permission.developmentSuffix')}
      </p>
    </div>
  )
}
