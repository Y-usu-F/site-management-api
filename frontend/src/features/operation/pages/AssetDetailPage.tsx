import { Link, useParams } from 'react-router-dom'
import { useTranslation } from 'react-i18next'
import { OperationStatusBadge } from '@/features/operation/components/OperationStatusBadge'
import { useOperationLookups } from '@/features/operation/hooks/useOperationLookups'
import { useAssetQuery } from '@/features/operation/hooks/useAssets'
import { PermissionDeniedNotice } from '@/shared/components/PermissionDeniedNotice'
import { useEffectiveCan } from '@/shared/hooks/useEffectiveCan'
import { parsePositiveInt } from '@/shared/lib/parseRouteId'

export function AssetDetailPage() {
  const { t } = useTranslation(['operations'])
  const canView = useEffectiveCan('asset.view')
  const canUpdate = useEffectiveCan('asset.update')
  const { siteMap, unitMap } = useOperationLookups()
  const { id } = useParams()
  const parsedId = parsePositiveInt(id)
  const query = useAssetQuery(parsedId ?? 0, canView && parsedId !== null)
  if (!canView) return <PermissionDeniedNotice permission="asset.view" />
  if (parsedId === null) return <div>{t('operations.common.invalidId')}</div>
  if (query.isLoading) return <div>{t('operations.common.loading')}</div>
  if (query.isError || !query.data) return <div>{t('operations.common.recordNotLoaded')}</div>
  const row = query.data
  return <div className="space-y-4"><div className="flex items-center justify-between"><h1 className="text-xl font-semibold">{t('operations.common.assets')} #{row.id}</h1><div className="flex gap-3 text-sm"><Link to="/operations/assets">{t('operations.common.back')}</Link>{canUpdate ? <Link to={`/operations/assets/${row.id}/edit`} className="text-violet-600">{t('operations.common.edit')}</Link> : null}</div></div><div className="rounded-xl border border-zinc-200 p-4 dark:border-zinc-800"><div>{t('operations.common.name')}: {row.name}</div><div>{t('operations.common.type')}: {row.asset_type}</div><div>{t('operations.common.site')}: {siteMap[row.site_id] ?? `#${row.site_id}`}</div><div>{t('operations.common.block')}: {row.block_id ?? '-'}</div><div>{t('operations.common.unit')}: {row.unit_id ? (unitMap[row.unit_id] ?? `#${row.unit_id}`) : '-'}</div><div>{t('operations.common.code')}: {row.asset_no ?? '-'}</div><div>{t('operations.common.purchaseDate')}: {row.purchase_date ?? '-'}</div><div>{t('operations.common.status')}: <OperationStatusBadge status={row.status} /></div></div></div>
}
