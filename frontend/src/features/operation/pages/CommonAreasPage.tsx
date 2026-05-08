import { useState } from 'react'
import { Link } from 'react-router-dom'
import { useTranslation } from 'react-i18next'
import { OperationStatusBadge } from '@/features/operation/components/OperationStatusBadge'
import { useOperationLookups } from '@/features/operation/hooks/useOperationLookups'
import { useCommonAreasQuery } from '@/features/operation/hooks/useCommonAreas'
import { EmptyState } from '@/shared/components/EmptyState'
import { PermissionDeniedNotice } from '@/shared/components/PermissionDeniedNotice'
import { useEffectiveCan } from '@/shared/hooks/useEffectiveCan'

export function CommonAreasPage() {
  const { t } = useTranslation(['operations', 'common'])
  const canList = useEffectiveCan('common_area.list')
  const canCreate = useEffectiveCan('common_area.create')
  const canView = useEffectiveCan('common_area.view')
  const { siteMap } = useOperationLookups()
  const [page, setPage] = useState(1)
  const [status, setStatus] = useState('')
  const query = useCommonAreasQuery({ page, per_page: 10, status: status || undefined }, canList)
  if (!canList) return <PermissionDeniedNotice permission="common_area.list" />
  const items = query.data?.items ?? []
  const total = query.data?.total ?? 0
  return <div className="space-y-4"><div className="flex items-center justify-between"><h1 className="text-xl font-semibold">{t('commonAreas', { ns: 'operations' })}</h1>{canCreate ? <Link to="/operations/common-areas/new" className="rounded bg-violet-600 px-3 py-2 text-sm text-white">{t('new', { ns: 'operations' })}</Link> : null}</div><label className="text-sm"><span className="mb-1 block text-zinc-600 dark:text-zinc-300">{t('status', { ns: 'operations' })}</span><select value={status} onChange={(event)=>{setStatus(event.target.value);setPage(1)}} className="rounded border border-zinc-300 px-2 py-1.5 dark:border-zinc-700 dark:bg-zinc-900"><option value="">{t('all', { ns: 'operations' })}</option><option value="active">active</option><option value="inactive">inactive</option></select></label><p className="text-sm text-zinc-500">{t('totalRecords', { ns: 'operations' })}: {total}</p>{query.isLoading ? <div className="text-sm text-zinc-500">{t('loading', { ns: 'operations' })}</div> : null}{query.isError ? <EmptyState title={t('listFailed', { ns: 'operations' })} description={t('errorGeneric', { ns: 'common' })} /> : null}{!query.isLoading && !query.isError && items.length===0 ? <EmptyState title={t('noData', { ns: 'operations' })} description={t('createFirst', { ns: 'operations' })} /> : null}{!query.isLoading && !query.isError && items.length>0 ? <table className="min-w-full overflow-hidden rounded-xl border border-zinc-200 text-sm dark:border-zinc-800"><thead className="bg-zinc-100 dark:bg-zinc-900"><tr><th className="px-3 py-2 text-left">ID</th><th className="px-3 py-2 text-left">{t('name', { ns: 'operations' })}</th><th className="px-3 py-2 text-left">{t('capacity', { ns: 'operations' })}</th><th className="px-3 py-2 text-left">{t('status', { ns: 'operations' })}</th><th className="px-3 py-2 text-left">{t('action', { ns: 'operations' })}</th></tr></thead><tbody>{items.map((row)=><tr key={row.id} className="border-t border-zinc-200 dark:border-zinc-800"><td className="px-3 py-2">{row.id}</td><td className="px-3 py-2">{row.name}<div className="text-xs text-zinc-500">{t('site', { ns: 'operations' })}: {siteMap[row.site_id] ?? `#${row.site_id}`}</div></td><td className="px-3 py-2">{row.capacity ?? '-'}</td><td className="px-3 py-2"><OperationStatusBadge status={row.status} /></td><td className="px-3 py-2">{canView ? <Link to={`/operations/common-areas/${row.id}`} className="text-violet-600">{t('open', { ns: 'operations' })}</Link> : '-'}</td></tr>)}</tbody></table> : null}<div className="flex items-center gap-2"><button type="button" disabled={page<=1} onClick={()=>setPage((p)=>p-1)} className="rounded border px-2 py-1 text-sm">{t('pagination.prev', { ns: 'common' })}</button><span className="text-sm">{t('page', { ns: 'operations' })} {page}</span><button type="button" disabled={(query.data?.items?.length??0)<10} onClick={()=>setPage((p)=>p+1)} className="rounded border px-2 py-1 text-sm">{t('pagination.next', { ns: 'common' })}</button></div></div>
}
