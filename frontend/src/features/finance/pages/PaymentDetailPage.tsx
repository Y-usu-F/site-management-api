import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { Link, useParams } from 'react-router-dom'

import { FinanceStatusBadge } from '@/features/finance/components/FinanceStatusBadge'
import { MoneyText } from '@/features/finance/components/MoneyText'
import { useCancelPaymentMutation } from '@/features/finance/hooks/usePaymentMutations'
import { usePaymentQuery } from '@/features/finance/hooks/usePaymentQuery'
import { ConfirmDialog } from '@/shared/components/ConfirmDialog'
import { PermissionDeniedNotice } from '@/shared/components/PermissionDeniedNotice'
import { useEffectiveCan } from '@/shared/hooks/useEffectiveCan'
import { useToast } from '@/shared/hooks/useToast'
import { formatPaymentMethod } from '@/features/finance/utils/financeFormat'
import { getErrorMessage } from '@/shared/lib/extractValidationErrors'
import { parsePositiveInt } from '@/shared/lib/parseRouteId'

export function PaymentDetailPage() {
  const { t } = useTranslation(['finance', 'common'])
  const { id } = useParams()
  const parsedId = parsePositiveInt(id)
  const canView = useEffectiveCan('payment.view')
  const canCancel = useEffectiveCan('payment.cancel')
  const query = usePaymentQuery(parsedId ?? 0, canView && parsedId !== null)
  const cancelMutation = useCancelPaymentMutation()
  const toast = useToast()
  const [open, setOpen] = useState(false)

  if (!canView) return <PermissionDeniedNotice permission="payment.view" />
  if (parsedId === null) return <div>{t('errorGeneric', { ns: 'common' })}</div>
  if (!query.data) return <div>{t('loading', { ns: 'common' })}</div>
  const row = query.data
  const canCancelByStatus = !['completed', 'cancelled', 'refunded'].includes(
    String(row.status).toLowerCase(),
  )

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <h1 className="text-xl font-semibold">{t('payments', { ns: 'finance' })} #{row.id}</h1>
        <Link to="/finance/payments" className="text-sm text-violet-600">
          {t('back', { ns: 'finance' })}
        </Link>
      </div>
      <div className="rounded-xl border border-zinc-200 p-4 dark:border-zinc-800">
        <div>{t('paymentNo', { ns: 'finance' })}: {row.payment_no}</div>
        <div>{t('site', { ns: 'finance' })}: {row.site_id}</div>
        <div>{t('unit', { ns: 'finance' })}: {row.unit_id ?? '-'}</div>
        <div>{t('resident', { ns: 'finance' })}: {row.resident_profile_id ?? '-'}</div>
        <div>{t('amount', { ns: 'finance' })}: <MoneyText amount={row.amount} currency={row.currency} /></div>
        <div>{t('allocated', { ns: 'finance' })}: <MoneyText amount={row.allocated_amount ?? 0} currency={row.currency} /></div>
        <div>{t('method', { ns: 'finance' })}: {formatPaymentMethod(row.method)}</div>
        <div>{t('status', { ns: 'finance' })}: <FinanceStatusBadge status={row.status} /></div>
        <div>{t('date', { ns: 'finance' })}: {row.payment_date}</div>
        <div>{t('description', { ns: 'finance' })}: {row.description ?? '-'}</div>
      </div>
      {canCancel && canCancelByStatus ? (
        <>
          <button type="button" className="text-sm text-red-600" onClick={() => setOpen(true)}>
            {t('cancelPayment', { ns: 'finance' })}
          </button>
          <ConfirmDialog
            isOpen={open}
            title={t('deleteConfirmTitle', { ns: 'finance' })}
            description={t('confirm', { ns: 'common' })}
            confirmText={cancelMutation.isPending ? t('pleaseWait', { ns: 'common' }) : t('cancelPayment', { ns: 'finance' })}
            cancelText={t('cancel', { ns: 'common' })}
            variant="danger"
            onClose={() => setOpen(false)}
            onConfirm={() =>
              cancelMutation.mutate(row.id, {
                onSuccess: () => {
                  toast.success(t('cancelPayment', { ns: 'finance' }))
                  setOpen(false)
                },
                onError: (err) => toast.error(getErrorMessage(err)),
              })
            }
          />
        </>
      ) : null}
    </div>
  )
}
