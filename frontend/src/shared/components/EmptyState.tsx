import type { ReactNode } from 'react'
import { useTranslation } from 'react-i18next'

interface EmptyStateProps {
  title: string
  description?: ReactNode
}

export function EmptyState({ title, description }: EmptyStateProps) {
  const { t } = useTranslation(['common'])

  return (
    <div className="rounded-xl border border-dashed border-zinc-300 bg-zinc-50 p-12 text-center dark:border-zinc-600 dark:bg-zinc-900/50">
      <p className="font-medium text-zinc-800 dark:text-zinc-200">{title || t('common.emptyTitle')}</p>
      {description ? (
        <p className="mt-2 text-sm text-zinc-600 dark:text-zinc-400">{description}</p>
      ) : (
        <p className="mt-2 text-sm text-zinc-600 dark:text-zinc-400">{t('common.emptyDescription')}</p>
      )}
    </div>
  )
}
