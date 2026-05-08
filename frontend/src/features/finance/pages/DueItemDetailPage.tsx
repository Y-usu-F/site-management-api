import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { Link, useParams } from 'react-router-dom'

import { cancelDueItem, updateDueItem } from '@/features/finance/api/dueItemApi'
import { FinanceStatusBadge } from '@/features/finance/components/FinanceStatusBadge'
import { MoneyText } from '@/features/finance/components/MoneyText'
import { useDueItemQuery } from '@/features/finance/hooks/useDueItemQuery'
import { ConfirmDialog } from '@/shared/components/ConfirmDialog'
import { PermissionDeniedNotice } from '@/shared/components/PermissionDeniedNotice'
import { useEffectiveCan } from '@/shared/hooks/useEffectiveCan'
import { useToast } from '@/shared/hooks/useToast'
import { getErrorMessage } from '@/shared/lib/extractValidationErrors'
import { parsePositiveInt } from '@/shared/lib/parseRouteId'
import { useMutation, useQueryClient } from '@tanstack/react-query'

export function DueItemDetailPage() {
  const { t } = useTranslation(['finance', 'common'])
  const { id } = useParams()
  const parsedId = parsePositiveInt(id)
  const canView = useEffectiveCan('due_item.view')
  const canUpdate = useEffectiveCan('due_item.update')
  const canCancel = useEffectiveCan('due_item.cancel')
  const query = useDueItemQuery(parsedId ?? 0, canView && parsedId !== null)
  const [paidAmount, setPaidAmount] = useState('')
  const [description, setDescription] = useState('')
  const [confirmOpen, setConfirmOpen] = useState(false)
  const toast = useToast()
  const qc = useQueryClient()

  const updateMutation = useMutation({
    mutationFn: (body: { paid_amount?: number; description?: string }) => {
      if (parsedId === null) throw new Error('invalid id')
      return updateDueItem(parsedId, body)
    },
    onSuccess: () => {
      void qc.invalidateQueries({ queryKey: ['due-items'] })
      toast.success(t('updateSuccess', { ns: 'finance' }))
    },
    onError: (err) => toast.error(getErrorMessage(err)),
  })

  const cancelMutation = useMutation({
    mutationFn: () => {
      if (parsedId === null) throw new Error('invalid id')
      return cancelDueItem(parsedId)
    },
    onSuccess: () => {
      void qc.invalidateQueries({ queryKey: ['due-items'] })
      toast.success(t('cancelDueItem', { ns: 'finance' }))
      setConfirmOpen(false)
    },
    onError: (err) => toast.error(getErrorMessage(err)),
  })

  if (!canView) return <PermissionDeniedNotice permission="due_item.view" />
  if (parsedId === null) return <div>{t('errorGeneric', { ns: 'common' })}</div>
  if (!query.data) return <div>{t('loading', { ns: 'common' })}</div>
  const row = query.data

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <h1 className="text-xl font-semibold">{t('dueItems', { ns: 'finance' })} #{row.id}</h1>
        <Link to="/finance/due-items" className="text-sm text-violet-600">
          {t('back', { ns: 'finance' })}
        </Link>
      </div>
      <div className="rounded-xl border border-zinc-200 p-4 dark:border-zinc-800">
        <div>{t('unit', { ns: 'finance' })}: {row.unit_id}</div>
        <div>{t('duePeriods', { ns: 'finance' })}: {row.due_period_id}</div>
        <div>{t('dueDefinition', { ns: 'finance' })}: {row.due_definition_id}</div>
        <div>{t('amount', { ns: 'finance' })}: <MoneyText amount={row.amount} currency={row.currency} /></div>
        <div>{t('paid', { ns: 'finance' })}: <MoneyText amount={row.paid_amount} currency={row.currency} /></div>
        <div>{t('remaining', { ns: 'finance' })}: <MoneyText amount={row.remaining_amount} currency={row.currency} /></div>
        <div>{t('status', { ns: 'finance' })}: <FinanceStatusBadge status={row.status} /></div>
        <div>{t('dueDate', { ns: 'finance' })}: {row.due_date}</div>
      </div>
      {canUpdate ? (
        <form
          className="space-y-3 rounded-xl border border-zinc-200 p-4 dark:border-zinc-800"
          onSubmit={(e) => {
            e.preventDefault()
            updateMutation.mutate({
              paid_amount: paidAmount ? Number(paidAmount) : undefined,
              description: description.trim() || undefined,
            })
          }}
        >
          <div className="text-sm font-medium">{t('quickUpdate', { ns: 'finance' })}</div>
          <div className="grid gap-3 sm:grid-cols-2">
            <input
              placeholder={t('paid', { ns: 'finance' })}
              value={paidAmount}
              onChange={(e) => setPaidAmount(e.target.value)}
              className="rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-950"
            />
            <input
              placeholder={t('description', { ns: 'finance' })}
              value={description}
              onChange={(e) => setDescription(e.target.value)}
              className="rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-950"
            />
          </div>
          <button type="submit" disabled={updateMutation.isPending} className="rounded bg-violet-600 px-3 py-2 text-sm text-white disabled:opacity-50">
            {updateMutation.isPending ? t('pleaseWait', { ns: 'common' }) : t('save', { ns: 'finance' })}
          </button>
        </form>
      ) : null}
      {canCancel ? (
        <>
          <button type="button" className="text-sm text-red-600" onClick={() => setConfirmOpen(true)}>
            {t('cancelDueItem', { ns: 'finance' })}
          </button>
          <ConfirmDialog
            isOpen={confirmOpen}
            title={t('deleteConfirmTitle', { ns: 'finance' })}
            description={t('confirm', { ns: 'common' })}
            confirmText={cancelMutation.isPending ? t('pleaseWait', { ns: 'common' }) : t('cancelDueItem', { ns: 'finance' })}
            cancelText={t('cancel', { ns: 'common' })}
            variant="danger"
            onClose={() => setConfirmOpen(false)}
            onConfirm={() => cancelMutation.mutate()}
          />
        </>
      ) : null}
    </div>
  )
}
