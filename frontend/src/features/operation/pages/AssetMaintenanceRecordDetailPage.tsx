import { Link, useParams } from 'react-router-dom'
import { OperationActionButtons } from '@/features/operation/components/OperationActionButtons'
import { OperationStatusBadge } from '@/features/operation/components/OperationStatusBadge'
import { useAssetMaintenanceRecordQuery } from '@/features/operation/hooks/useAssetMaintenanceRecords'
import { useOperationLookups } from '@/features/operation/hooks/useOperationLookups'
import { PermissionDeniedNotice } from '@/shared/components/PermissionDeniedNotice'
import { useEffectiveCan } from '@/shared/hooks/useEffectiveCan'
import { parsePositiveInt } from '@/shared/lib/parseRouteId'

export function AssetMaintenanceRecordDetailPage() {
  const canView = useEffectiveCan('asset_maintenance_record.view')
  const { assetMap } = useOperationLookups()
  const { id } = useParams()
  const parsedId = parsePositiveInt(id)
  const query = useAssetMaintenanceRecordQuery(parsedId ?? 0, canView && parsedId !== null)
  if (!canView) return <PermissionDeniedNotice permission="asset_maintenance_record.view" />
  if (parsedId === null) return <div>Gecersiz ID.</div>
  if (query.isLoading) return <div>Yukleniyor...</div>
  if (query.isError || !query.data) return <div>Kayit yuklenemedi.</div>
  const row = query.data
  return <div className="space-y-4"><div className="flex items-center justify-between"><h1 className="text-xl font-semibold">Maintenance record #{row.id}</h1><Link to="/operations/asset-maintenance-records" className="text-sm text-violet-600">Back</Link></div><OperationActionButtons entity="asset_maintenance_record" id={row.id} /><div className="rounded-xl border border-zinc-200 p-4 dark:border-zinc-800"><div>Asset: {assetMap[row.asset_id] ?? `#${row.asset_id}`}</div><div>Plan: {row.maintenance_plan_id ? `#${row.maintenance_plan_id}` : '-'}</div><div>Performed at: {row.performed_at}</div><div>Cost: {row.cost_amount ?? '-'} {row.currency ?? ''}</div><div>Status: <OperationStatusBadge status={row.status} /></div><div>Description: {row.description ?? '-'}</div></div></div>
}
