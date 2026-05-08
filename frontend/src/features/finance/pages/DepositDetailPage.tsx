import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { Link, useParams } from 'react-router-dom'

import { ConfirmDialog } from '@/shared/components/ConfirmDialog'
import { FinanceStatusBadge } from '@/features/finance/components/FinanceStatusBadge'
import { MoneyText } from '@/features/finance/components/MoneyText'
import {
  useApplyToDebtDepositMutation,
  useCancelDepositMutation,
  useDeductDepositMutation,
  useReceiveDepositMutation,
  useRefundDepositMutation,
  useUpdateDepositMutation,
} from '@/features/finance/hooks/useDepositMutations'
import { useDepositQuery, useDepositTransactionsQuery } from '@/features/finance/hooks/useDepositQuery'
import { PermissionDeniedNotice } from '@/shared/components/PermissionDeniedNotice'
import { useEffectiveCan } from '@/shared/hooks/useEffectiveCan'
import { useToast } from '@/shared/hooks/useToast'
import { formatFinanceStatus } from '@/features/finance/utils/financeFormat'
import { getErrorMessage } from '@/shared/lib/extractValidationErrors'
import { parsePositiveInt } from '@/shared/lib/parseRouteId'

export function DepositDetailPage() {
  const { t } = useTranslation(['finance', 'common'])
  const { id } = useParams()
  const parsedId = parsePositiveInt(id)
  const canView = useEffectiveCan('deposit.view')
  const canUpdate = useEffectiveCan('deposit.update')
  const canListTx = useEffectiveCan('deposit_transaction.list')
  const canDepositReceive = useEffectiveCan('deposit.receive')
  const canDepositRefund = useEffectiveCan('deposit.refund')
  const canDepositDeduct = useEffectiveCan('deposit.deduct')
  const canDepositApplyToDebt = useEffectiveCan('deposit.apply_to_debt')
  const canDepositCancel = useEffectiveCan('deposit.cancel')
  const canDepositTxCreate = useEffectiveCan('deposit_transaction.create')
  const canDepositTxRefund = useEffectiveCan('deposit_transaction.refund')
  const canDepositTxDeduct = useEffectiveCan('deposit_transaction.deduct')
  const canDepositTxApplyToDebt = useEffectiveCan('deposit_transaction.apply_to_debt')
  const canDepositTxCancel = useEffectiveCan('deposit_transaction.cancel')

  const canReceive = canDepositReceive || canDepositTxCreate
  const canRefund = canDepositRefund || canDepositTxRefund
  const canDeduct = canDepositDeduct || canDepositTxDeduct
  const canApplyToDebt = canDepositApplyToDebt || canDepositTxApplyToDebt
  const canCancel = canDepositCancel || canDepositTxCancel
  const query = useDepositQuery(parsedId ?? 0, canView && parsedId !== null)
  const txQuery = useDepositTransactionsQuery(parsedId ?? 0, canListTx && parsedId !== null)
  const updateMutation = useUpdateDepositMutation()
  const receiveMutation = useReceiveDepositMutation()
  const refundMutation = useRefundDepositMutation()
  const deductMutation = useDeductDepositMutation()
  const applyMutation = useApplyToDebtDepositMutation()
  const cancelMutation = useCancelDepositMutation()
  const toast = useToast()
  const [refundAmount, setRefundAmount] = useState('')
  const [deductAmount, setDeductAmount] = useState('')
  const [applyAmount, setApplyAmount] = useState('')
  const [applyDueItemId, setApplyDueItemId] = useState('')
  const [cancelOpen, setCancelOpen] = useState(false)

  if (!canView) return <PermissionDeniedNotice permission="deposit.view" />
  if (parsedId === null) return <div>{t('errorGeneric', { ns: 'common' })}</div>
  if (!query.data) return <div>{t('loading', { ns: 'common' })}</div>
  const row = query.data

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <h1 className="text-xl font-semibold">{t('deposits', { ns: 'finance' })} #{row.id}</h1>
        <Link to="/finance/deposits" className="text-sm text-violet-600">
          {t('back', { ns: 'finance' })}
        </Link>
      </div>
      <div className="rounded-xl border border-zinc-200 p-4 dark:border-zinc-800">
        <div>{t('paymentNo', { ns: 'finance' })}: {row.deposit_no}</div>
        <div>{t('site', { ns: 'finance' })}: {row.site_id}</div>
        <div>{t('resident', { ns: 'finance' })}: {row.resident_profile_id}</div>
        <div>{t('unit', { ns: 'finance' })}: {row.unit_id}</div>
        <div>{t('initial', { ns: 'finance' })}: <MoneyText amount={row.initial_amount} currency={row.currency} /></div>
        <div>{t('balance', { ns: 'finance' })}: <MoneyText amount={row.balance_amount} currency={row.currency} /></div>
        <div>{t('status', { ns: 'finance' })}: <FinanceStatusBadge status={row.status} /></div>
        <div>{t('receivedAt', { ns: 'finance' })}: {row.received_at ?? '-'}</div>
        <div>{t('notes', { ns: 'finance' })}: {row.notes ?? '-'}</div>
      </div>
      {canUpdate ? (
        <div>
          <button
            type="button"
            className="rounded bg-violet-600 px-3 py-2 text-sm text-white disabled:opacity-50"
            disabled={updateMutation.isPending}
            onClick={() =>
              updateMutation.mutate(
                { id: row.id, body: { notes: `${row.notes ?? ''}`.trim() || undefined } },
                {
                  onSuccess: () => toast.success(t('updateSuccess', { ns: 'finance' })),
                  onError: (err) => toast.error(getErrorMessage(err)),
                },
              )
            }
          >
            {updateMutation.isPending ? t('pleaseWait', { ns: 'common' }) : t('save', { ns: 'finance' })}
          </button>
        </div>
      ) : null}
      <div className="grid gap-3 rounded-xl border border-zinc-200 p-4 dark:border-zinc-800">
          <h2 className="text-sm font-semibold">{t('actions', { ns: 'finance' })}</h2>
        {canReceive ? (
          <button
            type="button"
            className="rounded bg-emerald-600 px-3 py-2 text-sm text-white disabled:opacity-50"
            disabled={receiveMutation.isPending}
            onClick={() =>
              receiveMutation.mutate(row.id, {
                onSuccess: () => toast.success(t('updateSuccess', { ns: 'finance' })),
                onError: (err) => toast.error(getErrorMessage(err)),
              })
            }
          >
            {receiveMutation.isPending ? t('pleaseWait', { ns: 'common' }) : t('save', { ns: 'finance' })}
          </button>
        ) : null}
        {canRefund ? (
          <div className="flex flex-wrap items-center gap-2">
            <input
              value={refundAmount}
              onChange={(e) => setRefundAmount(e.target.value)}
              placeholder={t('amount', { ns: 'finance' })}
              className="rounded border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-950"
            />
            <button
              type="button"
              className="rounded bg-amber-600 px-3 py-2 text-sm text-white disabled:opacity-50"
              disabled={refundMutation.isPending || !refundAmount}
              onClick={() =>
                refundMutation.mutate(
                  { id: row.id, amount: Number(refundAmount) },
                  {
                    onSuccess: () => {
                      toast.success(t('updateSuccess', { ns: 'finance' }))
                      setRefundAmount('')
                    },
                    onError: (err) => toast.error(getErrorMessage(err)),
                  },
                )
              }
            >
              {refundMutation.isPending ? t('pleaseWait', { ns: 'common' }) : t('refund', { ns: 'finance' })}
            </button>
          </div>
        ) : null}
        {canDeduct ? (
          <div className="flex flex-wrap items-center gap-2">
            <input
              value={deductAmount}
              onChange={(e) => setDeductAmount(e.target.value)}
              placeholder={t('amount', { ns: 'finance' })}
              className="rounded border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-950"
            />
            <button
              type="button"
              className="rounded bg-orange-600 px-3 py-2 text-sm text-white disabled:opacity-50"
              disabled={deductMutation.isPending || !deductAmount}
              onClick={() =>
                deductMutation.mutate(
                  { id: row.id, amount: Number(deductAmount) },
                  {
                    onSuccess: () => {
                      toast.success(t('updateSuccess', { ns: 'finance' }))
                      setDeductAmount('')
                    },
                    onError: (err) => toast.error(getErrorMessage(err)),
                  },
                )
              }
            >
              {deductMutation.isPending ? t('pleaseWait', { ns: 'common' }) : t('deduction', { ns: 'finance' })}
            </button>
          </div>
        ) : null}
        {canApplyToDebt ? (
          <div className="flex flex-wrap items-center gap-2">
            <input
              value={applyDueItemId}
              onChange={(e) => setApplyDueItemId(e.target.value)}
              placeholder={t('dueItems', { ns: 'finance' })}
              className="rounded border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-950"
            />
            <input
              value={applyAmount}
              onChange={(e) => setApplyAmount(e.target.value)}
              placeholder={t('amount', { ns: 'finance' })}
              className="rounded border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-950"
            />
            <button
              type="button"
              className="rounded bg-violet-600 px-3 py-2 text-sm text-white disabled:opacity-50"
              disabled={applyMutation.isPending || !applyDueItemId || !applyAmount}
              onClick={() =>
                applyMutation.mutate(
                  {
                    id: row.id,
                    due_item_id: Number(applyDueItemId),
                    amount: Number(applyAmount),
                  },
                  {
                    onSuccess: () => {
                      toast.success(t('updateSuccess', { ns: 'finance' }))
                      setApplyDueItemId('')
                      setApplyAmount('')
                    },
                    onError: (err) => toast.error(getErrorMessage(err)),
                  },
                )
              }
            >
              {applyMutation.isPending ? t('pleaseWait', { ns: 'common' }) : t('applyToDebt', { ns: 'finance' })}
            </button>
          </div>
        ) : null}
        {canCancel ? (
          <>
            <button
              type="button"
              className="w-fit rounded bg-red-600 px-3 py-2 text-sm text-white disabled:opacity-50"
              disabled={cancelMutation.isPending}
              onClick={() => setCancelOpen(true)}
            >
              {t('cancelDeposit', { ns: 'finance' })}
            </button>
            <ConfirmDialog
              isOpen={cancelOpen}
              title={t('deleteConfirmTitle', { ns: 'finance' })}
              description={t('confirm', { ns: 'common' })}
              confirmText={cancelMutation.isPending ? t('pleaseWait', { ns: 'common' }) : t('cancelDeposit', { ns: 'finance' })}
              cancelText={t('cancel', { ns: 'common' })}
              variant="danger"
              onClose={() => setCancelOpen(false)}
              onConfirm={() =>
                cancelMutation.mutate(row.id, {
                  onSuccess: () => {
                    toast.success(t('cancelDeposit', { ns: 'finance' }))
                    setCancelOpen(false)
                  },
                  onError: (err) => toast.error(getErrorMessage(err)),
                })
              }
            />
          </>
        ) : null}
      </div>
      {canListTx ? (
        <div className="space-y-2">
          <h2 className="text-lg font-semibold">{t('transactions', { ns: 'finance' })}</h2>
          {(txQuery.data?.items ?? []).length === 0 ? (
            <p className="text-sm text-zinc-500">{t('emptyDescription', { ns: 'common' })}</p>
          ) : (
            <div className="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-800">
              <table className="min-w-full text-sm">
                <thead className="bg-zinc-100 dark:bg-zinc-900">
                  <tr>
                    <th className="px-3 py-2 text-left">ID</th>
                    <th className="px-3 py-2 text-left">{t('type', { ns: 'finance' })}</th>
                    <th className="px-3 py-2 text-left">{t('amount', { ns: 'finance' })}</th>
                    <th className="px-3 py-2 text-left">{t('date', { ns: 'finance' })}</th>
                    <th className="px-3 py-2 text-left">{t('description', { ns: 'finance' })}</th>
                  </tr>
                </thead>
                <tbody>
                  {(txQuery.data?.items ?? []).map((tx) => (
                    <tr key={tx.id} className="border-t border-zinc-200 dark:border-zinc-800">
                      <td className="px-3 py-2">{tx.id}</td>
                      <td className="px-3 py-2">{formatFinanceStatus(tx.transaction_type)}</td>
                      <td className="px-3 py-2"><MoneyText amount={tx.amount} currency={tx.currency} /></td>
                      <td className="px-3 py-2">{tx.transaction_date}</td>
                      <td className="px-3 py-2">{tx.description ?? '-'}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
        </div>
      ) : null}
    </div>
  )
}
