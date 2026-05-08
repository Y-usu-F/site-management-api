import { useState } from 'react'
import { useTranslation } from 'react-i18next'

import type { OperationActionEntity, OperationActionName } from '@/features/operation/actions/config'
import { useOperationAction } from '@/features/operation/hooks/useOperationAction'
import { ConfirmDialog } from '@/shared/components/ConfirmDialog'
import { useToast } from '@/shared/hooks/useToast'
import { getErrorMessage } from '@/shared/lib/extractValidationErrors'

interface OperationActionButtonsProps {
  entity: OperationActionEntity
  id: number
  className?: string
}

function labelForAction(action: OperationActionName, t: (key: string, options?: Record<string, unknown>) => string): string {
  switch (action) {
    case 'approve':
      return t('actions.approve', { ns: 'operations' })
    case 'reject':
      return t('actions.reject', { ns: 'operations' })
    case 'cancel':
      return t('actions.cancel', { ns: 'operations' })
    case 'start':
      return t('actions.start', { ns: 'operations' })
    case 'complete':
      return t('actions.complete', { ns: 'operations' })
  }
}

export function OperationActionButtons({ entity, id, className }: OperationActionButtonsProps) {
  const { t } = useTranslation(['operations'])
  const toast = useToast()
  const [pendingAction, setPendingAction] = useState<OperationActionName | null>(null)

  const approve = useOperationAction(entity, 'approve')
  const reject = useOperationAction(entity, 'reject')
  const cancel = useOperationAction(entity, 'cancel')
  const start = useOperationAction(entity, 'start')
  const complete = useOperationAction(entity, 'complete')

  const actionMap = { approve, reject, cancel, start, complete }

  const visibleActions = (Object.entries(actionMap) as Array<[OperationActionName, (typeof actionMap)['approve']]>)
    .filter(([, state]) => state.actionConfig && state.canRun)
    .map(([name]) => name)

  if (visibleActions.length === 0) {
    return null
  }

  const current = pendingAction ? actionMap[pendingAction] : null
  const isLoading = current?.isPending ?? false

  return (
    <>
      <div className={className ?? 'flex flex-wrap items-center gap-2'}>
        {visibleActions.map((actionName) => (
          <button
            key={actionName}
            type="button"
            onClick={() => setPendingAction(actionName)}
            disabled={actionMap[actionName].isPending}
            className="rounded border border-zinc-300 px-2 py-1 text-xs hover:bg-zinc-100 disabled:opacity-50 dark:border-zinc-700 dark:hover:bg-zinc-800"
          >
            {labelForAction(actionName, t)}
          </button>
        ))}
      </div>
      <ConfirmDialog
        isOpen={pendingAction !== null}
        title={t('actions.confirmTitle', { ns: 'operations' })}
        description={pendingAction ? actionMap[pendingAction].actionConfig?.confirmText ?? '' : ''}
        confirmText={pendingAction ? labelForAction(pendingAction, t) : t('actions.fallbackConfirm', { ns: 'operations' })}
        cancelText={t('actions.fallbackCancel', { ns: 'operations' })}
        variant="danger"
        isLoading={isLoading}
        onClose={() => setPendingAction(null)}
        onConfirm={() => {
          if (!pendingAction) return
          actionMap[pendingAction].run(id, {
            onSuccess: () => {
              toast.success(t('actions.success', { ns: 'operations' }))
              setPendingAction(null)
            },
            onError: (error) => {
              toast.error(getErrorMessage(error))
              setPendingAction(null)
            },
          })
        }}
      />
    </>
  )
}

