import { Link, useParams } from 'react-router-dom'
import { useTranslation } from 'react-i18next'
import { OperationStatusBadge } from '@/features/operation/components/OperationStatusBadge'
import { useOperationLookups } from '@/features/operation/hooks/useOperationLookups'
import { useCommonAreaQuery } from '@/features/operation/hooks/useCommonAreas'
import { PermissionDeniedNotice } from '@/shared/components/PermissionDeniedNotice'
import { useEffectiveCan } from '@/shared/hooks/useEffectiveCan'
import { parsePositiveInt } from '@/shared/lib/parseRouteId'

export function CommonAreaDetailPage() {
  const { t } = useTranslation(['operations'])
  const canView = useEffectiveCan('common_area.view')
  const canUpdate = useEffectiveCan('common_area.update')
  const { siteMap } = useOperationLookups()
  const { id } = useParams()
  const parsedId = parsePositiveInt(id)
  const query = useCommonAreaQuery(parsedId ?? 0, canView && parsedId !== null)
  if (!canView) return <PermissionDeniedNotice permission="common_area.view" />
  if (parsedId === null) return <div>{t('operations.common.invalidId')}</div>
  if (query.isLoading) return <div>{t('operations.common.loading')}</div>
  if (query.isError || !query.data) return <div>{t('operations.common.recordNotLoaded')}</div>
  const row = query.data
  return <div className="space-y-4"><div className="flex items-center justify-between"><h1 className="text-xl font-semibold">{t('operations.common.commonArea')} #{row.id}</h1><div className="flex gap-3 text-sm"><Link to="/operations/common-areas">{t('operations.common.back')}</Link>{canUpdate ? <Link to={`/operations/common-areas/${row.id}/edit`} className="text-violet-600">{t('operations.common.edit')}</Link> : null}</div></div><div className="rounded-xl border border-zinc-200 p-4 dark:border-zinc-800"><div>{t('operations.common.name')}: {row.name}</div><div>{t('operations.common.site')}: {siteMap[row.site_id] ?? `#${row.site_id}`}</div><div>{t('operations.common.capacity')}: {row.capacity ?? '-'}</div><div>{t('operations.common.status')}: <OperationStatusBadge status={row.status} /></div><div>{t('operations.common.description')}: {row.description ?? '-'}</div></div></div>
}
