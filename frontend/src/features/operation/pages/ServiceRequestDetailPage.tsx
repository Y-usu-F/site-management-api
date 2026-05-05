import { Link, useParams } from 'react-router-dom'

import { useServiceRequestQuery } from '@/features/operation/hooks/useServiceRequests'
import { PermissionDeniedNotice } from '@/shared/components/PermissionDeniedNotice'
import { useEffectiveCan } from '@/shared/hooks/useEffectiveCan'
import { parsePositiveInt } from '@/shared/lib/parseRouteId'

export function ServiceRequestDetailPage() {
  const canView = useEffectiveCan('service_request.view')
  const canUpdate = useEffectiveCan('service_request.update')
  const { id } = useParams()
  const parsedId = parsePositiveInt(id)
  const query = useServiceRequestQuery(parsedId ?? 0, canView && parsedId !== null)

  if (!canView) return <PermissionDeniedNotice permission="service_request.view" />
  if (parsedId === null) return <div>Gecersiz ID.</div>
  if (!query.data) return <div>Yukleniyor...</div>
  const row = query.data

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between"><h1 className="text-xl font-semibold">Service request #{row.id}</h1><div className="flex gap-3 text-sm"><Link to="/operations/service-requests">Back</Link>{canUpdate ? <Link to={`/operations/service-requests/${row.id}/edit`} className="text-violet-600">Edit</Link> : null}</div></div>
      <div className="rounded-xl border border-zinc-200 p-4 dark:border-zinc-800">
        <div>Title: {row.title}</div><div>Description: {row.description}</div><div>Site: {row.site_id}</div><div>Unit: {row.unit_id ?? '-'}</div><div>Resident: {row.resident_profile_id ?? '-'}</div><div>Priority: {row.priority ?? '-'}</div><div>Status: {row.status ?? '-'}</div>
      </div>
    </div>
  )
}
