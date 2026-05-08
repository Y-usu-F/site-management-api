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
  if (parsedId === null) return <div>{t('common.errorGeneric')}</div>
  if (!query.data) return <div>{t('common.loading')}</div>
  const row = query.data
  const canCancelByStatus = !['completed', 'cancelled', 'refunded'].includes(
    String(row.status).toLowerCase(),
  )

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <h1 className="text-xl font-semibold">{t('finance.common.payments')} #{row.id}</h1>
        <Link to="/finance/payments" className="text-sm text-violet-600">
          {t('finance.common.back')}
        </Link>
      </div>
      <div className="rounded-xl border border-zinc-200 p-4 dark:border-zinc-800">
        <div>{t('finance.common.paymentNo')}: {row.payment_no}</div>
        <div>{t('finance.common.site')}: {row.site_id}</div>
        <div>{t('finance.common.unit')}: {row.unit_id ?? '-'}</div>
        <div>{t('finance.common.resident')}: {row.resident_profile_id ?? '-'}</div>
        <div>{t('finance.common.amount')}: <MoneyText amount={row.amount} currency={row.currency} /></div>
        <div>{t('finance.common.allocated')}: <MoneyText amount={row.allocated_amount ?? 0} currency={row.currency} /></div>
        <div>{t('finance.common.method')}: {formatPaymentMethod(row.method)}</div>
        <div>{t('finance.common.status')}: <FinanceStatusBadge status={row.status} /></div>
        <div>{t('finance.common.date')}: {row.payment_date}</div>
        <div>{t('finance.common.description')}: {row.description ?? '-'}</div>
      </div>
      {canCancel && canCancelByStatus ? (
        <>
          <button type="button" className="text-sm text-red-600" onClick={() => setOpen(true)}>
            {t('finance.common.cancelPayment')}
          </button>
          <ConfirmDialog
            isOpen={open}
            title={t('finance.common.deleteConfirmTitle')}
            description={t('common.confirm')}
            confirmText={cancelMutation.isPending ? t('common.pleaseWait') : t('finance.common.cancelPayment')}
            cancelText={t('common.cancel')}
            variant="danger"
            onClose={() => setOpen(false)}
            onConfirm={() =>
              cancelMutation.mutate(row.id, {
                onSuccess: () => {
                  toast.success(t('finance.common.cancelPayment'))
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
