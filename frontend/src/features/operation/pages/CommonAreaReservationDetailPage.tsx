import { Link, useParams } from 'react-router-dom'

import { OperationActionButtons } from '@/features/operation/components/OperationActionButtons'
import { OperationStatusBadge } from '@/features/operation/components/OperationStatusBadge'
import { useCommonAreaReservationQuery } from '@/features/operation/hooks/useCommonAreaReservations'
import { useOperationLookups } from '@/features/operation/hooks/useOperationLookups'
import { PermissionDeniedNotice } from '@/shared/components/PermissionDeniedNotice'
import { useEffectiveCan } from '@/shared/hooks/useEffectiveCan'
import { parsePositiveInt } from '@/shared/lib/parseRouteId'

export function CommonAreaReservationDetailPage() {
  const canView = useEffectiveCan('common_area_reservation.view')
  const canUpdate = useEffectiveCan('common_area_reservation.update')
  const { commonAreaMap, residentMap, unitMap } = useOperationLookups()
  const { id } = useParams()
  const parsedId = parsePositiveInt(id)
  const query = useCommonAreaReservationQuery(parsedId ?? 0, canView && parsedId !== null)
  if (!canView) return <PermissionDeniedNotice permission="common_area_reservation.view" />
  if (parsedId === null) return <div>Gecersiz ID.</div>
  if (query.isLoading) return <div>Yukleniyor...</div>
  if (query.isError || !query.data) return <div>Kayit yuklenemedi.</div>
  const row = query.data
  return <div className="space-y-4"><div className="flex items-center justify-between"><h1 className="text-xl font-semibold">Reservation #{row.id}</h1><div className="flex gap-3 text-sm"><Link to="/operations/common-area-reservations">Back</Link>{canUpdate ? <Link to={`/operations/common-area-reservations/${row.id}/edit`} className="text-violet-600">Edit</Link> : null}</div></div><OperationActionButtons entity="common_area_reservation" id={row.id} /><div className="rounded-xl border border-zinc-200 p-4 dark:border-zinc-800"><div>Common area: {commonAreaMap[row.common_area_id] ?? `#${row.common_area_id}`}</div><div>Resident: {row.resident_profile_id ? (residentMap[row.resident_profile_id] ?? `#${row.resident_profile_id}`) : '-'}</div><div>Unit: {row.unit_id ? (unitMap[row.unit_id] ?? `#${row.unit_id}`) : '-'}</div><div>Start: {row.start_at}</div><div>End: {row.end_at}</div><div>Status: <OperationStatusBadge status={row.status} /></div><div>Notes: {row.notes ?? '-'}</div></div></div>
}
