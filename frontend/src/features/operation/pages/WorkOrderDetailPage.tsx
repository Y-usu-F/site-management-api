import { Link, useParams } from 'react-router-dom'

import { OperationActionButtons } from '@/features/operation/components/OperationActionButtons'
import { useWorkOrderQuery } from '@/features/operation/hooks/useWorkOrders'
import { PermissionDeniedNotice } from '@/shared/components/PermissionDeniedNotice'
import { useEffectiveCan } from '@/shared/hooks/useEffectiveCan'
import { parsePositiveInt } from '@/shared/lib/parseRouteId'

export function WorkOrderDetailPage() {
  const canView = useEffectiveCan('work_order.view')
  const canUpdate = useEffectiveCan('work_order.update')
  const { id } = useParams()
  const parsedId = parsePositiveInt(id)
  const query = useWorkOrderQuery(parsedId ?? 0, canView && parsedId !== null)
  if (!canView) return <PermissionDeniedNotice permission="work_order.view" />
  if (parsedId === null) return <div>Gecersiz ID.</div>
  if (!query.data) return <div>Yukleniyor...</div>
  const row = query.data
  return <div className="space-y-4"><div className="flex items-center justify-between"><h1 className="text-xl font-semibold">Work order #{row.id}</h1><div className="flex gap-3 text-sm"><Link to="/operations/work-orders">Back</Link>{canUpdate ? <Link to={`/operations/work-orders/${row.id}/edit`} className="text-violet-600">Edit</Link> : null}</div></div><OperationActionButtons entity="work_order" id={row.id} /><div className="rounded-xl border border-zinc-200 p-4 dark:border-zinc-800"><div>Service request: {row.service_request_id}</div><div>Assigned user: {row.assigned_to_user_id ?? '-'}</div><div>Vendor: {row.vendor_name ?? '-'}</div><div>Start: {row.planned_start_at ?? '-'}</div><div>End: {row.planned_end_at ?? '-'}</div><div>Cost: {row.cost_amount ?? '-'} {row.currency ?? ''}</div><div>Status: {row.status ?? '-'}</div><div>Notes: {row.notes ?? '-'}</div></div></div>
}
