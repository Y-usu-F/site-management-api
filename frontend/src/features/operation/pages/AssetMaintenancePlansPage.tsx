import { useState } from 'react'
import { Link } from 'react-router-dom'

import { OperationActionButtons } from '@/features/operation/components/OperationActionButtons'
import { useAssetMaintenancePlansQuery } from '@/features/operation/hooks/useAssetMaintenancePlans'
import { EmptyState } from '@/shared/components/EmptyState'
import { PermissionDeniedNotice } from '@/shared/components/PermissionDeniedNotice'
import { useEffectiveCan } from '@/shared/hooks/useEffectiveCan'

export function AssetMaintenancePlansPage() {
  const canList = useEffectiveCan('asset_maintenance_plan.list')
  const canCreate = useEffectiveCan('asset_maintenance_plan.create')
  const canView = useEffectiveCan('asset_maintenance_plan.view')
  const [page, setPage] = useState(1)
  const [status, setStatus] = useState('')
  const query = useAssetMaintenancePlansQuery({ page, per_page: 10, status: status || undefined }, canList)
  if (!canList) return <PermissionDeniedNotice permission="asset_maintenance_plan.list" />
  const items = query.data?.items ?? []
  return <div className="space-y-4"><div className="flex items-center justify-between"><h1 className="text-xl font-semibold">Maintenance plans</h1>{canCreate ? <Link to="/operations/asset-maintenance-plans/new" className="rounded bg-violet-600 px-3 py-2 text-sm text-white">New</Link> : null}</div><label className="text-sm"><span className="mb-1 block text-zinc-600 dark:text-zinc-300">Status</span><select value={status} onChange={(event)=>{setStatus(event.target.value);setPage(1)}} className="rounded border border-zinc-300 px-2 py-1.5 dark:border-zinc-700 dark:bg-zinc-900"><option value="">All</option><option value="active">active</option><option value="paused">paused</option><option value="cancelled">cancelled</option></select></label>{items.length===0 ? <EmptyState title="Plan yok" description="Ilk plani olusturun." /> : <table className="min-w-full overflow-hidden rounded-xl border border-zinc-200 text-sm dark:border-zinc-800"><thead className="bg-zinc-100 dark:bg-zinc-900"><tr><th className="px-3 py-2 text-left">ID</th><th className="px-3 py-2 text-left">Asset</th><th className="px-3 py-2 text-left">Frequency</th><th className="px-3 py-2 text-left">Next due</th><th className="px-3 py-2 text-left">Status</th><th className="px-3 py-2 text-left">Action</th></tr></thead><tbody>{items.map((row)=><tr key={row.id} className="border-t border-zinc-200 dark:border-zinc-800"><td className="px-3 py-2">{row.id}</td><td className="px-3 py-2">{row.asset_id}</td><td className="px-3 py-2">{row.frequency_type}</td><td className="px-3 py-2">{row.next_due_date}</td><td className="px-3 py-2">{row.status ?? '-'}</td><td className="px-3 py-2"><div className="flex items-center gap-2">{canView ? <Link to={`/operations/asset-maintenance-plans/${row.id}`} className="text-violet-600">Open</Link> : null}<OperationActionButtons entity="asset_maintenance_plan" id={row.id} /></div></td></tr>)}</tbody></table>}<div className="flex items-center gap-2"><button type="button" disabled={page<=1} onClick={()=>setPage((p)=>p-1)} className="rounded border px-2 py-1 text-sm">Prev</button><span className="text-sm">Page {page}</span><button type="button" disabled={(query.data?.items?.length??0)<10} onClick={()=>setPage((p)=>p+1)} className="rounded border px-2 py-1 text-sm">Next</button></div></div>
}
