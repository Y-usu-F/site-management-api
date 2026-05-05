import { Link, useParams } from 'react-router-dom'
import { useAssetQuery } from '@/features/operation/hooks/useAssets'
import { PermissionDeniedNotice } from '@/shared/components/PermissionDeniedNotice'
import { useEffectiveCan } from '@/shared/hooks/useEffectiveCan'
import { parsePositiveInt } from '@/shared/lib/parseRouteId'

export function AssetDetailPage() {
  const canView = useEffectiveCan('asset.view')
  const canUpdate = useEffectiveCan('asset.update')
  const { id } = useParams()
  const parsedId = parsePositiveInt(id)
  const query = useAssetQuery(parsedId ?? 0, canView && parsedId !== null)
  if (!canView) return <PermissionDeniedNotice permission="asset.view" />
  if (parsedId === null) return <div>Gecersiz ID.</div>
  if (!query.data) return <div>Yukleniyor...</div>
  const row = query.data
  return <div className="space-y-4"><div className="flex items-center justify-between"><h1 className="text-xl font-semibold">Asset #{row.id}</h1><div className="flex gap-3 text-sm"><Link to="/operations/assets">Back</Link>{canUpdate ? <Link to={`/operations/assets/${row.id}/edit`} className="text-violet-600">Edit</Link> : null}</div></div><div className="rounded-xl border border-zinc-200 p-4 dark:border-zinc-800"><div>Name: {row.name}</div><div>Type: {row.asset_type}</div><div>Site: {row.site_id}</div><div>Block: {row.block_id ?? '-'}</div><div>Unit: {row.unit_id ?? '-'}</div><div>Code: {row.asset_no ?? '-'}</div><div>Purchase date: {row.purchase_date ?? '-'}</div><div>Status: {row.status ?? '-'}</div></div></div>
}
