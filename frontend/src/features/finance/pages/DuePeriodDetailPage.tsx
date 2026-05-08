import { Link, useNavigate, useParams } from 'react-router-dom'
import { useTranslation } from 'react-i18next'

import { useDeleteDuePeriodMutation } from '@/features/finance/hooks/useDuePeriodMutations'
import { useDuePeriodQuery } from '@/features/finance/hooks/useDuePeriodQuery'
import { FinanceStatusBadge } from '@/features/finance/components/FinanceStatusBadge'
import { ConfirmDialog } from '@/shared/components/ConfirmDialog'
import { PermissionDeniedNotice } from '@/shared/components/PermissionDeniedNotice'
import { useEffectiveCan } from '@/shared/hooks/useEffectiveCan'
import { useToast } from '@/shared/hooks/useToast'
import { getErrorMessage } from '@/shared/lib/extractValidationErrors'
import { parsePositiveInt } from '@/shared/lib/parseRouteId'
import { useState } from 'react'

export function DuePeriodDetailPage() {
  const { t } = useTranslation(['finance', 'common'])
  const { id } = useParams()
  const parsedId = parsePositiveInt(id)
  const canView = useEffectiveCan('due_period.view')
  const canDelete = useEffectiveCan('due_period.delete')
  const query = useDuePeriodQuery(parsedId ?? 0, canView && parsedId !== null)
  const del = useDeleteDuePeriodMutation()
  const [open, setOpen] = useState(false)
  const navigate = useNavigate()
  const toast = useToast()

  if (!canView) return <PermissionDeniedNotice permission="due_period.view" />
  if (parsedId === null) return <div>{t('common.errorGeneric')}</div>
  if (!query.data) return <div>{t('common.loading')}</div>

  const row = query.data
  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <h1 className="text-xl font-semibold">{row.period_key}</h1>
        <div className="flex gap-3 text-sm">
          <Link to="/finance/due-periods">{t('finance.common.back')}</Link>
          <Link className="text-violet-600" to={`/finance/due-periods/${row.id}/edit`}>
            {t('finance.common.edit')}
          </Link>
          {canDelete ? (
            <button type="button" className="text-red-600" onClick={() => setOpen(true)}>
              {t('finance.common.delete')}
            </button>
          ) : null}
        </div>
      </div>
      <div className="rounded-xl border border-zinc-200 p-4 dark:border-zinc-800">
        <div>ID: {row.id}</div>
        <div>{t('finance.common.siteId')}: {row.site_id}</div>
        <div>{t('finance.common.startDate')}: {row.start_date}</div>
        <div>{t('finance.common.endDate')}: {row.end_date}</div>
        <div>{t('finance.common.dueDate')}: {row.due_date}</div>
        <div>{t('finance.common.status')}: <FinanceStatusBadge status={row.status} /></div>
      </div>
      <ConfirmDialog
        isOpen={open}
        title={t('finance.common.deleteConfirmTitle')}
        description={t('common.confirm')}
        confirmText={del.isPending ? t('finance.common.deleting') : t('finance.common.delete')}
        cancelText={t('common.cancel')}
        variant="danger"
        onClose={() => setOpen(false)}
        onConfirm={() => {
          del.mutate(row.id, {
            onSuccess: () => {
              toast.success(t('finance.common.deleteSuccess'))
              navigate('/finance/due-periods')
            },
            onError: (err) => toast.error(getErrorMessage(err)),
          })
        }}
      />
    </div>
  )
}
