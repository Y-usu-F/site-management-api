import { Link, useParams } from 'react-router-dom'
import { useTranslation } from 'react-i18next'
import { OperationActionButtons } from '@/features/operation/components/OperationActionButtons'
import { OperationStatusBadge } from '@/features/operation/components/OperationStatusBadge'
import { useAssetMaintenancePlanQuery } from '@/features/operation/hooks/useAssetMaintenancePlans'
import { useOperationLookups } from '@/features/operation/hooks/useOperationLookups'
import { PermissionDeniedNotice } from '@/shared/components/PermissionDeniedNotice'
import { useEffectiveCan } from '@/shared/hooks/useEffectiveCan'
import { parsePositiveInt } from '@/shared/lib/parseRouteId'

export function AssetMaintenancePlanDetailPage() {
  const { t } = useTranslation(['operations'])
  const canView = useEffectiveCan('asset_maintenance_plan.view')
  const canUpdate = useEffectiveCan('asset_maintenance_plan.update')
  const { assetMap } = useOperationLookups()
  const { id } = useParams()
  const parsedId = parsePositiveInt(id)
  const query = useAssetMaintenancePlanQuery(parsedId ?? 0, canView && parsedId !== null)
  if (!canView) return <PermissionDeniedNotice permission="asset_maintenance_plan.view" />
  if (parsedId === null) return <div>{t('invalidId', { ns: 'operations' })}</div>
  if (query.isLoading) return <div>{t('loading', { ns: 'operations' })}</div>
  if (query.isError || !query.data) return <div>{t('recordNotLoaded', { ns: 'operations' })}</div>
  const row = query.data
  return <div className="space-y-4"><div className="flex items-center justify-between"><h1 className="text-xl font-semibold">{t('maintenancePlan', { ns: 'operations' })} #{row.id}</h1><div className="flex gap-3 text-sm"><Link to="/operations/asset-maintenance-plans">{t('back', { ns: 'operations' })}</Link>{canUpdate ? <Link to={`/operations/asset-maintenance-plans/${row.id}/edit`} className="text-violet-600">{t('edit', { ns: 'operations' })}</Link> : null}</div></div><OperationActionButtons entity="asset_maintenance_plan" id={row.id} /><div className="rounded-xl border border-zinc-200 p-4 dark:border-zinc-800"><div>{t('assets', { ns: 'operations' })}: {assetMap[row.asset_id] ?? `#${row.asset_id}`}</div><div>{t('frequency', { ns: 'operations' })}: {row.frequency_type}</div><div>{t('interval', { ns: 'operations' })}: {row.frequency_interval ?? '-'}</div><div>{t('nextDue', { ns: 'operations' })}: {row.next_due_date}</div><div>{t('status', { ns: 'operations' })}: <OperationStatusBadge status={row.status} /></div><div>{t('vendor', { ns: 'operations' })}: {row.vendor_name ?? '-'}</div><div>{t('notes', { ns: 'operations' })}: {row.notes ?? '-'}</div></div></div>
}
