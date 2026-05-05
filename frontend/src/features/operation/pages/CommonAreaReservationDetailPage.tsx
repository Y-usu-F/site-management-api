import { Link, useParams } from 'react-router-dom'
import { useCommonAreaReservationQuery } from '@/features/operation/hooks/useCommonAreaReservations'
import { PermissionDeniedNotice } from '@/shared/components/PermissionDeniedNotice'
import { useEffectiveCan } from '@/shared/hooks/useEffectiveCan'
import { parsePositiveInt } from '@/shared/lib/parseRouteId'

export function CommonAreaReservationDetailPage() {
  const canView = useEffectiveCan('common_area_reservation.view')
  const canUpdate = useEffectiveCan('common_area_reservation.update')
  const { id } = useParams()
  const parsedId = parsePositiveInt(id)
  const query = useCommonAreaReservationQuery(parsedId ?? 0, canView && parsedId !== null)
  if (!canView) return <PermissionDeniedNotice permission="common_area_reservation.view" />
  if (parsedId === null) return <div>Gecersiz ID.</div>
  if (!query.data) return <div>Yukleniyor...</div>
  const row = query.data
  return <div className="space-y-4"><div className="flex items-center justify-between"><h1 className="text-xl font-semibold">Reservation #{row.id}</h1><div className="flex gap-3 text-sm"><Link to="/operations/common-area-reservations">Back</Link>{canUpdate ? <Link to={`/operations/common-area-reservations/${row.id}/edit`} className="text-violet-600">Edit</Link> : null}</div></div><div className="rounded-xl border border-zinc-200 p-4 dark:border-zinc-800"><div>Common area: {row.common_area_id}</div><div>Resident: {row.resident_profile_id ?? '-'}</div><div>Unit: {row.unit_id ?? '-'}</div><div>Start: {row.start_at}</div><div>End: {row.end_at}</div><div>Status: {row.status ?? '-'}</div><div>Notes: {row.notes ?? '-'}</div></div></div>
}
