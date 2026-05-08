import { Link, useParams } from 'react-router-dom'
import { useTranslation } from 'react-i18next'

import { OperationActionButtons } from '@/features/operation/components/OperationActionButtons'
import { OperationStatusBadge } from '@/features/operation/components/OperationStatusBadge'
import { useWorkOrderQuery } from '@/features/operation/hooks/useWorkOrders'
import { PermissionDeniedNotice } from '@/shared/components/PermissionDeniedNotice'
import { useEffectiveCan } from '@/shared/hooks/useEffectiveCan'
import { parsePositiveInt } from '@/shared/lib/parseRouteId'

export function WorkOrderDetailPage() {
  const { t } = useTranslation(['operations'])
  const canView = useEffectiveCan('work_order.view')
  const canUpdate = useEffectiveCan('work_order.update')
  const { id } = useParams()
  const parsedId = parsePositiveInt(id)
  const query = useWorkOrderQuery(parsedId ?? 0, canView && parsedId !== null)
  if (!canView) return <PermissionDeniedNotice permission="work_order.view" />
  if (parsedId === null) return <div>{t('operations.common.invalidId')}</div>
  if (query.isLoading) return <div>{t('operations.common.loading')}</div>
  if (query.isError || !query.data) return <div>{t('operations.common.recordNotLoaded')}</div>
  const row = query.data
  return <div className="space-y-4"><div className="flex items-center justify-between"><h1 className="text-xl font-semibold">{t('operations.common.workOrders')} #{row.id}</h1><div className="flex gap-3 text-sm"><Link to="/operations/work-orders">{t('operations.common.back')}</Link>{canUpdate ? <Link to={`/operations/work-orders/${row.id}/edit`} className="text-violet-600">{t('operations.common.edit')}</Link> : null}</div></div><OperationActionButtons entity="work_order" id={row.id} /><div className="rounded-xl border border-zinc-200 p-4 dark:border-zinc-800"><div>{t('operations.common.serviceRequest')}: #{row.service_request_id}</div><div>{t('operations.common.assignedUser')}: {row.assigned_to_user_id ? `#${row.assigned_to_user_id}` : '-'}</div><div>{t('operations.common.vendor')}: {row.vendor_name ?? '-'}</div><div>{t('operations.common.start')}: {row.planned_start_at ?? '-'}</div><div>{t('operations.common.end')}: {row.planned_end_at ?? '-'}</div><div>{t('operations.common.cost')}: {row.cost_amount ?? '-'} {row.currency ?? ''}</div><div>{t('operations.common.status')}: <OperationStatusBadge status={row.status} /></div><div>{t('operations.common.notes')}: {row.notes ?? '-'}</div></div></div>
}
