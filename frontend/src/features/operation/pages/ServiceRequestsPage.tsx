import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { Link } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'

import { listLookupSites } from '@/features/operation/api/lookupsApi'
import type { LookupOption } from '@/features/operation/api/lookupsApi'
import { OperationActionButtons } from '@/features/operation/components/OperationActionButtons'
import { OperationStatusBadge } from '@/features/operation/components/OperationStatusBadge'
import { useServiceRequestsQuery } from '@/features/operation/hooks/useServiceRequests'
import { EmptyState } from '@/shared/components/EmptyState'
import { PermissionDeniedNotice } from '@/shared/components/PermissionDeniedNotice'
import { useEffectiveCan } from '@/shared/hooks/useEffectiveCan'
import { useOperationLookups } from '@/features/operation/hooks/useOperationLookups'

export function ServiceRequestsPage() {
  const { t } = useTranslation(['operations', 'common'])
  const canList = useEffectiveCan('service_request.list')
  const canCreate = useEffectiveCan('service_request.create')
  const canView = useEffectiveCan('service_request.view')
  const { siteMap, unitMap, residentMap } = useOperationLookups()

  const [page, setPage] = useState(1)
  const [status, setStatus] = useState('')
  const [siteId, setSiteId] = useState('')

  const query = useServiceRequestsQuery(
    {
      page,
      per_page: 10,
      status: status || undefined,
      site_id: siteId ? Number(siteId) : undefined,
    },
    canList,
  )
  const sitesQuery = useQuery<LookupOption[]>({
    queryKey: ['operation', 'lookups', 'sites'],
    queryFn: () => listLookupSites(''),
    enabled: canList,
  })

  if (!canList) return <PermissionDeniedNotice permission="service_request.list" />
  const items = query.data?.items ?? []
  const total = query.data?.total ?? 0

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <h1 className="text-xl font-semibold">{t('operations.common.serviceRequests')}</h1>
        {canCreate ? (
          <Link to="/operations/service-requests/new" className="rounded bg-violet-600 px-3 py-2 text-sm text-white">
            {t('operations.common.new')}
          </Link>
        ) : null}
      </div>
      <p className="text-sm text-zinc-500">{t('operations.common.totalRecords')}: {total}</p>
      <div className="flex flex-wrap items-end gap-2">
        <label className="text-sm">
          <span className="mb-1 block text-zinc-600 dark:text-zinc-300">{t('operations.common.status')}</span>
          <select
            value={status}
            onChange={(event) => {
              setStatus(event.target.value)
              setPage(1)
            }}
            className="rounded border border-zinc-300 px-2 py-1.5 dark:border-zinc-700 dark:bg-zinc-900"
          >
            <option value="">{t('operations.common.all')}</option>
            <option value="open">open</option>
            <option value="assigned">assigned</option>
            <option value="in_progress">in_progress</option>
            <option value="resolved">resolved</option>
            <option value="closed">closed</option>
            <option value="cancelled">cancelled</option>
          </select>
        </label>
        <label className="text-sm">
          <span className="mb-1 block text-zinc-600 dark:text-zinc-300">{t('operations.common.site')}</span>
          <select
            value={siteId}
            onChange={(event) => {
              setSiteId(event.target.value)
              setPage(1)
            }}
            className="rounded border border-zinc-300 px-2 py-1.5 dark:border-zinc-700 dark:bg-zinc-900"
          >
            <option value="">{t('operations.common.all')}</option>
            {(sitesQuery.data ?? []).map((site) => (
              <option key={site.id} value={String(site.id)}>
                {site.label}
              </option>
            ))}
          </select>
        </label>
      </div>
      {query.isLoading ? <div className="text-sm text-zinc-500">Yukleniyor...</div> : null}
      {query.isError ? <EmptyState title={t('operations.common.listFailed')} description={t('common.errorGeneric')} /> : null}
      {!query.isLoading && !query.isError && items.length === 0 ? <EmptyState title={t('common.emptyTitle')} description={t('operations.common.createFirst')} /> : null}
      {!query.isLoading && !query.isError && items.length > 0 ? (
        <table className="min-w-full overflow-hidden rounded-xl border border-zinc-200 text-sm dark:border-zinc-800">
          <thead className="bg-zinc-100 dark:bg-zinc-900">
            <tr>
              <th className="px-3 py-2 text-left">ID</th>
              <th className="px-3 py-2 text-left">{t('operations.common.title')}</th>
              <th className="px-3 py-2 text-left">{t('operations.common.priority')}</th>
              <th className="px-3 py-2 text-left">{t('operations.common.status')}</th>
              <th className="px-3 py-2 text-left">{t('operations.common.action')}</th>
            </tr>
          </thead>
          <tbody>
            {items.map((row) => (
              <tr key={row.id} className="border-t border-zinc-200 dark:border-zinc-800">
                <td className="px-3 py-2">{row.id}</td>
                <td className="px-3 py-2">{row.title}</td>
                <td className="px-3 py-2">{row.priority ?? '-'}</td>
                <td className="px-3 py-2">
                  <OperationStatusBadge status={row.status} />
                </td>
                <td className="px-3 py-2">
                  <div className="text-xs text-zinc-500">
                    {t('operations.common.site')}: {siteMap[row.site_id] ?? `#${row.site_id}`} | {t('operations.common.unit')}:{' '}
                    {row.unit_id ? (unitMap[row.unit_id] ?? `#${row.unit_id}`) : '-'} | Resident:{' '}
                    {row.resident_profile_id ? (residentMap[row.resident_profile_id] ?? `#${row.resident_profile_id}`) : '-'}
                  </div>
                  <div className="flex items-center gap-2">
                    {canView ? (
                      <Link className="text-violet-600" to={`/operations/service-requests/${row.id}`}>
                        {t('operations.common.open')}
                      </Link>
                    ) : null}
                    <OperationActionButtons entity="service_request" id={row.id} />
                  </div>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      ) : null}
      <div className="flex items-center gap-2">
        <button type="button" disabled={page <= 1} onClick={() => setPage((p) => p - 1)} className="rounded border px-2 py-1 text-sm">{t('common.pagination.prev')}</button>
        <span className="text-sm">{t('operations.common.page')} {page}</span>
        <button type="button" disabled={(query.data?.items?.length ?? 0) < 10} onClick={() => setPage((p) => p + 1)} className="rounded border px-2 py-1 text-sm">{t('common.pagination.next')}</button>
      </div>
    </div>
  )
}
