import { Link, useParams } from 'react-router-dom'
import { useTranslation } from 'react-i18next'

import { OperationActionButtons } from '@/features/operation/components/OperationActionButtons'
import { OperationStatusBadge } from '@/features/operation/components/OperationStatusBadge'
import { useServiceRequestQuery } from '@/features/operation/hooks/useServiceRequests'
import { useOperationLookups } from '@/features/operation/hooks/useOperationLookups'
import { PermissionDeniedNotice } from '@/shared/components/PermissionDeniedNotice'
import { useEffectiveCan } from '@/shared/hooks/useEffectiveCan'
import { parsePositiveInt } from '@/shared/lib/parseRouteId'

export function ServiceRequestDetailPage() {
  const { t } = useTranslation(['operations'])
  const canView = useEffectiveCan('service_request.view')
  const canUpdate = useEffectiveCan('service_request.update')
  const { siteMap, unitMap, residentMap } = useOperationLookups()
  const { id } = useParams()
  const parsedId = parsePositiveInt(id)
  const query = useServiceRequestQuery(parsedId ?? 0, canView && parsedId !== null)

  if (!canView) return <PermissionDeniedNotice permission="service_request.view" />
  if (parsedId === null) return <div>{t('operations.common.invalidId')}</div>
  if (query.isLoading) return <div>{t('operations.common.loading')}</div>
  if (query.isError || !query.data) return <div>{t('operations.common.recordNotLoaded')}</div>
  const row = query.data

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between"><h1 className="text-xl font-semibold">{t('operations.common.serviceRequest')} #{row.id}</h1><div className="flex gap-3 text-sm"><Link to="/operations/service-requests">{t('operations.common.back')}</Link>{canUpdate ? <Link to={`/operations/service-requests/${row.id}/edit`} className="text-violet-600">{t('operations.common.edit')}</Link> : null}</div></div>
      <OperationActionButtons entity="service_request" id={row.id} />
      <div className="rounded-xl border border-zinc-200 p-4 dark:border-zinc-800">
        <div>{t('operations.common.title')}: {row.title}</div><div>{t('operations.common.description')}: {row.description}</div><div>{t('operations.common.site')}: {siteMap[row.site_id] ?? `#${row.site_id}`}</div><div>{t('operations.common.unit')}: {row.unit_id ? (unitMap[row.unit_id] ?? `#${row.unit_id}`) : '-'}</div><div>{t('operations.common.resident')}: {row.resident_profile_id ? (residentMap[row.resident_profile_id] ?? `#${row.resident_profile_id}`) : '-'}</div><div>{t('operations.common.priority')}: {row.priority ?? '-'}</div><div>{t('operations.common.status')}: <OperationStatusBadge status={row.status} /></div>
      </div>
    </div>
  )
}
