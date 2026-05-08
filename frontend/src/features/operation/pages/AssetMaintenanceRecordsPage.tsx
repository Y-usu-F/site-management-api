import { useState } from 'react'
import { Link } from 'react-router-dom'
import { useTranslation } from 'react-i18next'

import { OperationActionButtons } from '@/features/operation/components/OperationActionButtons'
import { OperationStatusBadge } from '@/features/operation/components/OperationStatusBadge'
import { useAssetMaintenanceRecordsQuery } from '@/features/operation/hooks/useAssetMaintenanceRecords'
import { useOperationLookups } from '@/features/operation/hooks/useOperationLookups'
import { EmptyState } from '@/shared/components/EmptyState'
import { PermissionDeniedNotice } from '@/shared/components/PermissionDeniedNotice'
import { useEffectiveCan } from '@/shared/hooks/useEffectiveCan'

export function AssetMaintenanceRecordsPage() {
  const { t } = useTranslation(['operations', 'common'])
  const canList = useEffectiveCan('asset_maintenance_record.list')
  const canCreate = useEffectiveCan('asset_maintenance_record.create')
  const canView = useEffectiveCan('asset_maintenance_record.view')
  const { assetMap } = useOperationLookups()
  const [page, setPage] = useState(1)
  const [status, setStatus] = useState('')
  const query = useAssetMaintenanceRecordsQuery({ page, per_page: 10, status: status || undefined }, canList)
  if (!canList) return <PermissionDeniedNotice permission="asset_maintenance_record.list" />
  const items = query.data?.items ?? []
  const total = query.data?.total ?? 0
  return <div className="space-y-4"><div className="flex items-center justify-between"><h1 className="text-xl font-semibold">{t('operations.common.maintenanceRecords')}</h1>{canCreate ? <Link to="/operations/asset-maintenance-records/new" className="rounded bg-violet-600 px-3 py-2 text-sm text-white">{t('operations.common.new')}</Link> : null}</div><label className="text-sm"><span className="mb-1 block text-zinc-600 dark:text-zinc-300">{t('operations.common.status')}</span><select value={status} onChange={(event)=>{setStatus(event.target.value);setPage(1)}} className="rounded border border-zinc-300 px-2 py-1.5 dark:border-zinc-700 dark:bg-zinc-900"><option value="">{t('operations.common.all')}</option><option value="completed">{t('operations.common.statusCompleted')}</option><option value="cancelled">{t('operations.common.statusCancelled')}</option></select></label><p className="text-sm text-zinc-500">{t('operations.common.totalRecords')}: {total}</p>{query.isLoading ? <div className="text-sm text-zinc-500">{t('operations.common.loading')}</div> : null}{query.isError ? <EmptyState title={t('operations.common.listFailed')} description={t('common.errorGeneric')} /> : null}{!query.isLoading && !query.isError && items.length===0 ? <EmptyState title={t('operations.common.noData')} description={t('operations.common.createFirst')} /> : null}{!query.isLoading && !query.isError && items.length>0 ? <table className="min-w-full overflow-hidden rounded-xl border border-zinc-200 text-sm dark:border-zinc-800"><thead className="bg-zinc-100 dark:bg-zinc-900"><tr><th className="px-3 py-2 text-left">ID</th><th className="px-3 py-2 text-left">{t('operations.common.assets')}</th><th className="px-3 py-2 text-left">{t('operations.common.performed')}</th><th className="px-3 py-2 text-left">{t('operations.common.cost')}</th><th className="px-3 py-2 text-left">{t('operations.common.status')}</th><th className="px-3 py-2 text-left">{t('operations.common.action')}</th></tr></thead><tbody>{items.map((row)=><tr key={row.id} className="border-t border-zinc-200 dark:border-zinc-800"><td className="px-3 py-2">{row.id}</td><td className="px-3 py-2">{assetMap[row.asset_id] ?? `#${row.asset_id}`}</td><td className="px-3 py-2">{row.performed_at}</td><td className="px-3 py-2">{row.cost_amount ?? '-'} {row.currency ?? ''}</td><td className="px-3 py-2"><OperationStatusBadge status={row.status} /></td><td className="px-3 py-2"><div className="flex items-center gap-2">{canView ? <Link className="text-violet-600" to={`/operations/asset-maintenance-records/${row.id}`}>{t('operations.common.open')}</Link> : null}<OperationActionButtons entity="asset_maintenance_record" id={row.id} /></div></td></tr>)}</tbody></table> : null}<div className="flex items-center gap-2"><button type="button" disabled={page<=1} onClick={()=>setPage((p)=>p-1)} className="rounded border px-2 py-1 text-sm">{t('common.pagination.prev')}</button><span className="text-sm">{t('operations.common.page')} {page}</span><button type="button" disabled={(query.data?.items?.length??0)<10} onClick={()=>setPage((p)=>p+1)} className="rounded border px-2 py-1 text-sm">{t('common.pagination.next')}</button></div></div>
}
