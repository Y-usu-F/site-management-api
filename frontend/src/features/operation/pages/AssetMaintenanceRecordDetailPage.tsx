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
  if (parsedId === null) return <div>{t('invalidId', { ns: 'operations' })}</div>
  if (query.isLoading) return <div>{t('loading', { ns: 'operations' })}</div>
  if (query.isError || !query.data) return <div>{t('recordNotLoaded', { ns: 'operations' })}</div>
  const row = query.data
  return <div className="space-y-4"><div className="flex items-center justify-between"><h1 className="text-xl font-semibold">{t('maintenanceRecord', { ns: 'operations' })} #{row.id}</h1><Link to="/operations/asset-maintenance-records" className="text-sm text-violet-600">{t('back', { ns: 'operations' })}</Link></div><OperationActionButtons entity="asset_maintenance_record" id={row.id} /><div className="rounded-xl border border-zinc-200 p-4 dark:border-zinc-800"><div>{t('assets', { ns: 'operations' })}: {assetMap[row.asset_id] ?? `#${row.asset_id}`}</div><div>{t('plan', { ns: 'operations' })}: {row.maintenance_plan_id ? `#${row.maintenance_plan_id}` : '-'}</div><div>{t('performed', { ns: 'operations' })}: {row.performed_at}</div><div>{t('cost', { ns: 'operations' })}: {row.cost_amount ?? '-'} {row.currency ?? ''}</div><div>{t('status', { ns: 'operations' })}: <OperationStatusBadge status={row.status} /></div><div>{t('description', { ns: 'operations' })}: {row.description ?? '-'}</div></div></div>
}
