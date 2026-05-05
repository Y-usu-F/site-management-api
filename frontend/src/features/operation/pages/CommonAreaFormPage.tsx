import { Link, useNavigate, useParams } from 'react-router-dom'
import { CommonAreaForm } from '@/features/operation/components/CommonAreaForm'
import { useCommonAreaQuery, useCreateCommonAreaMutation, useUpdateCommonAreaMutation } from '@/features/operation/hooks/useCommonAreas'
import { PermissionDeniedNotice } from '@/shared/components/PermissionDeniedNotice'
import { useEffectiveCan } from '@/shared/hooks/useEffectiveCan'
import { useToast } from '@/shared/hooks/useToast'
import { getErrorMessage } from '@/shared/lib/extractValidationErrors'
import { parsePositiveInt } from '@/shared/lib/parseRouteId'

interface Props { mode: 'create' | 'edit' }
export function CommonAreaFormPage({ mode }: Props) {
  const canCreate = useEffectiveCan('common_area.create')
  const canUpdate = useEffectiveCan('common_area.update')
  const canAccess = mode === 'create' ? canCreate : canUpdate
  const { id } = useParams()
  const parsedId = parsePositiveInt(id)
  const detail = useCommonAreaQuery(parsedId ?? 0, mode === 'edit' && parsedId !== null)
  const createMutation = useCreateCommonAreaMutation()
  const updateMutation = useUpdateCommonAreaMutation()
  const mutation = mode === 'create' ? createMutation : updateMutation
  const navigate = useNavigate()
  const toast = useToast()
  if (!canAccess) return <PermissionDeniedNotice permission={mode === 'create' ? 'common_area.create' : 'common_area.update'} />
  if (mode === 'edit' && parsedId === null) return <div>Gecersiz ID.</div>
  return <div className="space-y-4"><div className="flex items-center justify-between"><h1 className="text-xl font-semibold">{mode === 'create' ? 'New common area' : 'Edit common area'}</h1><Link to="/operations/common-areas" className="text-sm text-violet-600">Back</Link></div><CommonAreaForm defaultValues={mode==='edit' ? (detail.data as unknown as Record<string, unknown>) : undefined} isSubmitting={mutation.isPending} submitLabel={mode==='create' ? 'Create' : 'Save'} onSubmit={(values)=>{if(mode==='create'){createMutation.mutate(values,{onSuccess:(created)=>{toast.success('Common area olusturuldu.');navigate(`/operations/common-areas/${created.id}`)},onError:(err)=>toast.error(getErrorMessage(err))})}else if(parsedId!==null){updateMutation.mutate({id:parsedId,body:values},{onSuccess:()=>{toast.success('Common area guncellendi.');navigate(`/operations/common-areas/${parsedId}`)},onError:(err)=>toast.error(getErrorMessage(err))})}}} /></div>
}
