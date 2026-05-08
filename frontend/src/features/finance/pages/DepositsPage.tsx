import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { Link } from 'react-router-dom'

import { FinanceStatusBadge } from '@/features/finance/components/FinanceStatusBadge'
import { MoneyText } from '@/features/finance/components/MoneyText'
import { useDepositsQuery } from '@/features/finance/hooks/useDepositsQuery'
import { EmptyState } from '@/shared/components/EmptyState'
import { PermissionDeniedNotice } from '@/shared/components/PermissionDeniedNotice'
import { useEffectiveCan } from '@/shared/hooks/useEffectiveCan'

export function DepositsPage() {
  const { t } = useTranslation(['finance', 'common'])
  const canList = useEffectiveCan('deposit.list')
  const canCreate = useEffectiveCan('deposit.create')
  const [page, setPage] = useState(1)
  const query = useDepositsQuery({ page, per_page: 10 }, canList)

  if (!canList) return <PermissionDeniedNotice permission="deposit.list" />

  const items = query.data?.items ?? []
  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <h1 className="text-xl font-semibold">{t('deposits', { ns: 'finance' })}</h1>
        {canCreate ? (
          <Link to="/finance/deposits/new" className="rounded-lg bg-violet-600 px-3 py-2 text-sm text-white">
            {t('create', { ns: 'common' })}
          </Link>
        ) : null}
      </div>
      {items.length === 0 ? (
        <EmptyState title={t('noDeposit', { ns: 'finance' })} description={t('emptyDescription', { ns: 'common' })} />
      ) : (
        <div className="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-800">
          <table className="min-w-full text-sm">
            <thead className="bg-zinc-100 dark:bg-zinc-900">
              <tr>
                <th className="px-3 py-2 text-left">ID</th>
                <th className="px-3 py-2 text-left">{t('paymentNo', { ns: 'finance' })}</th>
                <th className="px-3 py-2 text-left">{t('resident', { ns: 'finance' })}</th>
                <th className="px-3 py-2 text-left">{t('unit', { ns: 'finance' })}</th>
                <th className="px-3 py-2 text-left">{t('initial', { ns: 'finance' })}</th>
                <th className="px-3 py-2 text-left">{t('balance', { ns: 'finance' })}</th>
                <th className="px-3 py-2 text-left">{t('status', { ns: 'finance' })}</th>
                <th className="px-3 py-2 text-left">{t('action', { ns: 'finance' })}</th>
              </tr>
            </thead>
            <tbody>
              {items.map((row) => (
                <tr key={row.id} className="border-t border-zinc-200 dark:border-zinc-800">
                  <td className="px-3 py-2">{row.id}</td>
                  <td className="px-3 py-2">{row.deposit_no}</td>
                  <td className="px-3 py-2">{row.resident_profile_id}</td>
                  <td className="px-3 py-2">{row.unit_id}</td>
                  <td className="px-3 py-2"><MoneyText amount={row.initial_amount} currency={row.currency} /></td>
                  <td className="px-3 py-2"><MoneyText amount={row.balance_amount} currency={row.currency} /></td>
                  <td className="px-3 py-2"><FinanceStatusBadge status={row.status} /></td>
                  <td className="px-3 py-2">
                    <Link to={`/finance/deposits/${row.id}`} className="text-violet-600">
                      {t('open', { ns: 'finance' })}
                    </Link>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
      <div className="flex items-center gap-2">
        <button type="button" className="rounded border px-2 py-1 text-sm" disabled={page <= 1} onClick={() => setPage((p) => p - 1)}>{t('pagination.prev', { ns: 'common' })}</button>
        <span className="text-sm">{t('page', { ns: 'finance' })} {page}</span>
        <button type="button" className="rounded border px-2 py-1 text-sm" disabled={(query.data?.items?.length ?? 0) < 10} onClick={() => setPage((p) => p + 1)}>{t('pagination.next', { ns: 'common' })}</button>
      </div>
    </div>
  )
}
