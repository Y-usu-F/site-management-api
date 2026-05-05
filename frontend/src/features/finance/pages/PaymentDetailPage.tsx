import { useState } from 'react'
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
  const { id } = useParams()
  const parsedId = parsePositiveInt(id)
  const canView = useEffectiveCan('payment.view')
  const canCancel = useEffectiveCan('payment.cancel')
  const query = usePaymentQuery(parsedId ?? 0, canView && parsedId !== null)
  const cancelMutation = useCancelPaymentMutation()
  const toast = useToast()
  const [open, setOpen] = useState(false)

  if (!canView) return <PermissionDeniedNotice permission="payment.view" />
  if (parsedId === null) return <div>Gecersiz ID.</div>
  if (!query.data) return <div>Yukleniyor...</div>
  const row = query.data
  const canCancelByStatus = !['completed', 'cancelled', 'refunded'].includes(
    String(row.status).toLowerCase(),
  )

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <h1 className="text-xl font-semibold">Payment #{row.id}</h1>
        <Link to="/finance/payments" className="text-sm text-violet-600">
          Back
        </Link>
      </div>
      <div className="rounded-xl border border-zinc-200 p-4 dark:border-zinc-800">
        <div>No: {row.payment_no}</div>
        <div>Site: {row.site_id}</div>
        <div>Unit: {row.unit_id ?? '-'}</div>
        <div>Resident: {row.resident_profile_id ?? '-'}</div>
        <div>Amount: <MoneyText amount={row.amount} currency={row.currency} /></div>
        <div>Allocated: <MoneyText amount={row.allocated_amount ?? 0} currency={row.currency} /></div>
        <div>Method: {formatPaymentMethod(row.method)}</div>
        <div>Status: <FinanceStatusBadge status={row.status} /></div>
        <div>Date: {row.payment_date}</div>
        <div>Description: {row.description ?? '-'}</div>
      </div>
      {canCancel && canCancelByStatus ? (
        <>
          <button type="button" className="text-sm text-red-600" onClick={() => setOpen(true)}>
            Cancel payment
          </button>
          <ConfirmDialog
            isOpen={open}
            title="Payment iptal edilsin mi?"
            description="Duruma gore backend reddedebilir."
            confirmText={cancelMutation.isPending ? 'Cancelling…' : 'Cancel'}
            cancelText="Vazgec"
            variant="danger"
            onClose={() => setOpen(false)}
            onConfirm={() =>
              cancelMutation.mutate(row.id, {
                onSuccess: () => {
                  toast.success('Payment iptal edildi.')
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
