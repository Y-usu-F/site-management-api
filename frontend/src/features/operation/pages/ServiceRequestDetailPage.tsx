import { Link, useParams } from 'react-router-dom'

import { OperationActionButtons } from '@/features/operation/components/OperationActionButtons'
import { OperationStatusBadge } from '@/features/operation/components/OperationStatusBadge'
import { useServiceRequestQuery } from '@/features/operation/hooks/useServiceRequests'
import { useOperationLookups } from '@/features/operation/hooks/useOperationLookups'
import { PermissionDeniedNotice } from '@/shared/components/PermissionDeniedNotice'
import { useEffectiveCan } from '@/shared/hooks/useEffectiveCan'
import { parsePositiveInt } from '@/shared/lib/parseRouteId'

export function ServiceRequestDetailPage() {
  const canView = useEffectiveCan('service_request.view')
  const canUpdate = useEffectiveCan('service_request.update')
  const { siteMap, unitMap, residentMap } = useOperationLookups()
  const { id } = useParams()
  const parsedId = parsePositiveInt(id)
  const query = useServiceRequestQuery(parsedId ?? 0, canView && parsedId !== null)

  if (!canView) return <PermissionDeniedNotice permission="service_request.view" />
  if (parsedId === null) return <div>Gecersiz ID.</div>
  if (query.isLoading) return <div>Yukleniyor...</div>
  if (query.isError || !query.data) return <div>Kayit yuklenemedi.</div>
  const row = query.data

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between"><h1 className="text-xl font-semibold">Service request #{row.id}</h1><div className="flex gap-3 text-sm"><Link to="/operations/service-requests">Back</Link>{canUpdate ? <Link to={`/operations/service-requests/${row.id}/edit`} className="text-violet-600">Edit</Link> : null}</div></div>
      <OperationActionButtons entity="service_request" id={row.id} />
      <div className="rounded-xl border border-zinc-200 p-4 dark:border-zinc-800">
        <div>Title: {row.title}</div><div>Description: {row.description}</div><div>Site: {siteMap[row.site_id] ?? `#${row.site_id}`}</div><div>Unit: {row.unit_id ? (unitMap[row.unit_id] ?? `#${row.unit_id}`) : '-'}</div><div>Resident: {row.resident_profile_id ? (residentMap[row.resident_profile_id] ?? `#${row.resident_profile_id}`) : '-'}</div><div>Priority: {row.priority ?? '-'}</div><div>Status: <OperationStatusBadge status={row.status} /></div>
      </div>
    </div>
  )
}
