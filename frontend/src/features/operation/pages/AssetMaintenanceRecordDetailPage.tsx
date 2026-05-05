import { Link, useParams } from 'react-router-dom'
import { useAssetMaintenanceRecordQuery } from '@/features/operation/hooks/useAssetMaintenanceRecords'
import { PermissionDeniedNotice } from '@/shared/components/PermissionDeniedNotice'
import { useEffectiveCan } from '@/shared/hooks/useEffectiveCan'
import { parsePositiveInt } from '@/shared/lib/parseRouteId'

export function AssetMaintenanceRecordDetailPage() {
  const canView = useEffectiveCan('asset_maintenance_record.view')
  const { id } = useParams()
  const parsedId = parsePositiveInt(id)
  const query = useAssetMaintenanceRecordQuery(parsedId ?? 0, canView && parsedId !== null)
  if (!canView) return <PermissionDeniedNotice permission="asset_maintenance_record.view" />
  if (parsedId === null) return <div>Gecersiz ID.</div>
  if (!query.data) return <div>Yukleniyor...</div>
  const row = query.data
  return <div className="space-y-4"><div className="flex items-center justify-between"><h1 className="text-xl font-semibold">Maintenance record #{row.id}</h1><Link to="/operations/asset-maintenance-records" className="text-sm text-violet-600">Back</Link></div><div className="rounded-xl border border-zinc-200 p-4 dark:border-zinc-800"><div>Asset: {row.asset_id}</div><div>Plan: {row.maintenance_plan_id ?? '-'}</div><div>Performed at: {row.performed_at}</div><div>Cost: {row.cost_amount ?? '-'} {row.currency ?? ''}</div><div>Status: {row.status ?? '-'}</div><div>Description: {row.description ?? '-'}</div></div></div>
}
