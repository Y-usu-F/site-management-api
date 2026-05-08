import { Link, useNavigate, useParams } from 'react-router-dom'
import { useTranslation } from 'react-i18next'
import { CommonAreaReservationForm } from '@/features/operation/components/CommonAreaReservationForm'
import { useCommonAreaReservationQuery, useCreateCommonAreaReservationMutation, useUpdateCommonAreaReservationMutation } from '@/features/operation/hooks/useCommonAreaReservations'
import { PermissionDeniedNotice } from '@/shared/components/PermissionDeniedNotice'
import { useEffectiveCan } from '@/shared/hooks/useEffectiveCan'
import { useToast } from '@/shared/hooks/useToast'
import { getErrorMessage } from '@/shared/lib/extractValidationErrors'
import { parsePositiveInt } from '@/shared/lib/parseRouteId'

interface Props { mode: 'create' | 'edit' }
export function CommonAreaReservationFormPage({ mode }: Props) {
  const { t } = useTranslation(['operations'])
  const canCreate = useEffectiveCan('common_area_reservation.create')
  const canUpdate = useEffectiveCan('common_area_reservation.update')
  const canAccess = mode === 'create' ? canCreate : canUpdate
  const { id } = useParams()
  const parsedId = parsePositiveInt(id)
  const detail = useCommonAreaReservationQuery(parsedId ?? 0, mode === 'edit' && parsedId !== null)
  const createMutation = useCreateCommonAreaReservationMutation()
  const updateMutation = useUpdateCommonAreaReservationMutation()
  const mutation = mode === 'create' ? createMutation : updateMutation
  const navigate = useNavigate()
  const toast = useToast()
  if (!canAccess) return <PermissionDeniedNotice permission={mode === 'create' ? 'common_area_reservation.create' : 'common_area_reservation.update'} />
  if (mode === 'edit' && parsedId === null) return <div>{t('invalidId', { ns: 'operations' })}</div>
  return <div className="space-y-4"><div className="flex items-center justify-between"><h1 className="text-xl font-semibold">{mode === 'create' ? t('create', { ns: 'operations' }) : t('edit', { ns: 'operations' })} {t('reservations', { ns: 'operations' })}</h1><Link to="/operations/common-area-reservations" className="text-sm text-violet-600">{t('back', { ns: 'operations' })}</Link></div><CommonAreaReservationForm defaultValues={mode==='edit' ? (detail.data as unknown as Record<string, unknown>) : undefined} isSubmitting={mutation.isPending} submitLabel={mode==='create' ? t('create', { ns: 'operations' }) : t('save', { ns: 'operations' })} onSubmit={(values)=>{if(mode==='create'){createMutation.mutate(values,{onSuccess:(created)=>{toast.success(t('createSuccess', { ns: 'operations' }));navigate(`/operations/common-area-reservations/${created.id}`)},onError:(err)=>toast.error(getErrorMessage(err))})}else if(parsedId!==null){updateMutation.mutate({id:parsedId,body:values},{onSuccess:()=>{toast.success(t('updateSuccess', { ns: 'operations' }));navigate(`/operations/common-area-reservations/${parsedId}`)},onError:(err)=>toast.error(getErrorMessage(err))})}}} /></div>
}
