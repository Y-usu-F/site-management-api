import { Link, useParams } from 'react-router-dom'
import { OperationStatusBadge } from '@/features/operation/components/OperationStatusBadge'
import { useOperationLookups } from '@/features/operation/hooks/useOperationLookups'
import { useCommonAreaQuery } from '@/features/operation/hooks/useCommonAreas'
import { PermissionDeniedNotice } from '@/shared/components/PermissionDeniedNotice'
import { useEffectiveCan } from '@/shared/hooks/useEffectiveCan'
import { parsePositiveInt } from '@/shared/lib/parseRouteId'

export function CommonAreaDetailPage() {
  const canView = useEffectiveCan('common_area.view')
  const canUpdate = useEffectiveCan('common_area.update')
  const { siteMap } = useOperationLookups()
  const { id } = useParams()
  const parsedId = parsePositiveInt(id)
  const query = useCommonAreaQuery(parsedId ?? 0, canView && parsedId !== null)
  if (!canView) return <PermissionDeniedNotice permission="common_area.view" />
  if (parsedId === null) return <div>Gecersiz ID.</div>
  if (query.isLoading) return <div>Yukleniyor...</div>
  if (query.isError || !query.data) return <div>Kayit yuklenemedi.</div>
  const row = query.data
  return <div className="space-y-4"><div className="flex items-center justify-between"><h1 className="text-xl font-semibold">Common area #{row.id}</h1><div className="flex gap-3 text-sm"><Link to="/operations/common-areas">Back</Link>{canUpdate ? <Link to={`/operations/common-areas/${row.id}/edit`} className="text-violet-600">Edit</Link> : null}</div></div><div className="rounded-xl border border-zinc-200 p-4 dark:border-zinc-800"><div>Name: {row.name}</div><div>Site: {siteMap[row.site_id] ?? `#${row.site_id}`}</div><div>Capacity: {row.capacity ?? '-'}</div><div>Status: <OperationStatusBadge status={row.status} /></div><div>Description: {row.description ?? '-'}</div></div></div>
}
