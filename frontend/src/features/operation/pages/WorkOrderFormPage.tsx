import { Link, useNavigate, useParams } from 'react-router-dom'
import { useTranslation } from 'react-i18next'
import { WorkOrderForm } from '@/features/operation/components/WorkOrderForm'
import { useCreateWorkOrderMutation, useUpdateWorkOrderMutation, useWorkOrderQuery } from '@/features/operation/hooks/useWorkOrders'
import { PermissionDeniedNotice } from '@/shared/components/PermissionDeniedNotice'
import { useEffectiveCan } from '@/shared/hooks/useEffectiveCan'
import { useToast } from '@/shared/hooks/useToast'
import { extractValidationErrors, getErrorMessage } from '@/shared/lib/extractValidationErrors'
import { parsePositiveInt } from '@/shared/lib/parseRouteId'

interface Props { mode: 'create' | 'edit' }
export function WorkOrderFormPage({ mode }: Props) {
  const { t } = useTranslation(['operations', 'common'])
  const canCreate = useEffectiveCan('work_order.create')
  const canUpdate = useEffectiveCan('work_order.update')
  const canAccess = mode === 'create' ? canCreate : canUpdate
  const { id } = useParams()
  const parsedId = parsePositiveInt(id)
  const detail = useWorkOrderQuery(parsedId ?? 0, mode === 'edit' && parsedId !== null)
  const createMutation = useCreateWorkOrderMutation()
  const updateMutation = useUpdateWorkOrderMutation()
  const navigate = useNavigate()
  const toast = useToast()
  const mutation = mode === 'create' ? createMutation : updateMutation
  if (!canAccess) return <PermissionDeniedNotice permission={mode === 'create' ? 'work_order.create' : 'work_order.update'} />
  if (mode === 'edit' && parsedId === null) return <div>{t('operations.common.invalidId')}</div>
  return <div className="space-y-4"><div className="flex items-center justify-between"><h1 className="text-xl font-semibold">{mode === 'create' ? t('operations.common.create') : t('operations.common.edit')} {t('operations.common.workOrders')}</h1><Link to="/operations/work-orders" className="text-sm text-violet-600">{t('operations.common.back')}</Link></div><WorkOrderForm defaultValues={mode === 'edit' ? (detail.data as unknown as Record<string, unknown>) : undefined} isSubmitting={mutation.isPending} submitLabel={mode === 'create' ? t('operations.common.create') : t('operations.common.save')} onSubmit={(values)=>{if(mode==='create'){createMutation.mutate(values,{onSuccess:(created)=>{toast.success(t('operations.common.createSuccess'));navigate(`/operations/work-orders/${created.id}`)},onError:(err)=>toast.error(getErrorMessage(err))})}else if(parsedId!==null){updateMutation.mutate({id:parsedId,body:values},{onSuccess:()=>{toast.success(t('operations.common.updateSuccess'));navigate(`/operations/work-orders/${parsedId}`)},onError:(err)=>toast.error(getErrorMessage(err))})}}} />{extractValidationErrors(mutation.error)._form ? <p className="text-sm text-red-600">{extractValidationErrors(mutation.error)._form}</p> : null}</div>
}
