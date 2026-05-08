import { Link, useParams } from 'react-router-dom'
import { useTranslation } from 'react-i18next'
import { OperationActionButtons } from '@/features/operation/components/OperationActionButtons'
import { OperationStatusBadge } from '@/features/operation/components/OperationStatusBadge'
import { useAssetMaintenanceRecordQuery } from '@/features/operation/hooks/useAssetMaintenanceRecords'
import { useOperationLookups } from '@/features/operation/hooks/useOperationLookups'
import { PermissionDeniedNotice } from '@/shared/components/PermissionDeniedNotice'
import { useEffectiveCan } from '@/shared/hooks/useEffectiveCan'
import { parsePositiveInt } from '@/shared/lib/parseRouteId'

export function AssetMaintenanceRecordDetailPage() {
  const { t } = useTranslation(['operations'])
  const canView = useEffectiveCan('asset_maintenance_record.view')
  const { assetMap } = useOperationLookups()
  const { id } = useParams()
  const parsedId = parsePositiveInt(id)
  const query = useAssetMaintenanceRecordQuery(parsedId ?? 0, canView && parsedId !== null)
  if (!canView) return <PermissionDeniedNotice permission="asset_maintenance_record.view" />
  if (parsedId === null) return <div>{t('operations.common.invalidId')}</div>
  if (query.isLoading) return <div>{t('operations.common.loading')}</div>
  if (query.isError || !query.data) return <div>{t('operations.common.recordNotLoaded')}</div>
  const row = query.data
  return <div className="space-y-4"><div className="flex items-center justify-between"><h1 className="text-xl font-semibold">{t('operations.common.maintenanceRecord')} #{row.id}</h1><Link to="/operations/asset-maintenance-records" className="text-sm text-violet-600">{t('operations.common.back')}</Link></div><OperationActionButtons entity="asset_maintenance_record" id={row.id} /><div className="rounded-xl border border-zinc-200 p-4 dark:border-zinc-800"><div>{t('operations.common.assets')}: {assetMap[row.asset_id] ?? `#${row.asset_id}`}</div><div>{t('operations.common.plan')}: {row.maintenance_plan_id ? `#${row.maintenance_plan_id}` : '-'}</div><div>{t('operations.common.performed')}: {row.performed_at}</div><div>{t('operations.common.cost')}: {row.cost_amount ?? '-'} {row.currency ?? ''}</div><div>{t('operations.common.status')}: <OperationStatusBadge status={row.status} /></div><div>{t('operations.common.description')}: {row.description ?? '-'}</div></div></div>
}
