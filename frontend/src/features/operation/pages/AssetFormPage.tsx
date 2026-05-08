import { Link, useNavigate, useParams } from 'react-router-dom'
import { useTranslation } from 'react-i18next'
import { AssetForm } from '@/features/operation/components/AssetForm'
import { useAssetQuery, useCreateAssetMutation, useUpdateAssetMutation } from '@/features/operation/hooks/useAssets'
import { PermissionDeniedNotice } from '@/shared/components/PermissionDeniedNotice'
import { useEffectiveCan } from '@/shared/hooks/useEffectiveCan'
import { useToast } from '@/shared/hooks/useToast'
import { getErrorMessage } from '@/shared/lib/extractValidationErrors'
import { parsePositiveInt } from '@/shared/lib/parseRouteId'

interface Props { mode: 'create' | 'edit' }
export function AssetFormPage({ mode }: Props) {
  const { t } = useTranslation(['operations'])
  const canCreate = useEffectiveCan('asset.create')
  const canUpdate = useEffectiveCan('asset.update')
  const canAccess = mode === 'create' ? canCreate : canUpdate
  const { id } = useParams()
  const parsedId = parsePositiveInt(id)
  const detail = useAssetQuery(parsedId ?? 0, mode === 'edit' && parsedId !== null)
  const createMutation = useCreateAssetMutation()
  const updateMutation = useUpdateAssetMutation()
  const mutation = mode === 'create' ? createMutation : updateMutation
  const navigate = useNavigate()
  const toast = useToast()
  if (!canAccess) return <PermissionDeniedNotice permission={mode === 'create' ? 'asset.create' : 'asset.update'} />
  if (mode === 'edit' && parsedId === null) return <div>{t('operations.common.invalidId')}</div>
  return <div className="space-y-4"><div className="flex items-center justify-between"><h1 className="text-xl font-semibold">{mode === 'create' ? t('operations.common.create') : t('operations.common.edit')} {t('operations.common.assets')}</h1><Link to="/operations/assets" className="text-sm text-violet-600">{t('operations.common.back')}</Link></div><AssetForm defaultValues={mode==='edit' ? (detail.data as unknown as Record<string, unknown>) : undefined} isSubmitting={mutation.isPending} submitLabel={mode==='create' ? t('operations.common.create') : t('operations.common.save')} onSubmit={(values)=>{if(mode==='create'){createMutation.mutate(values,{onSuccess:(created)=>{toast.success(t('operations.common.createSuccess'));navigate(`/operations/assets/${created.id}`)},onError:(err)=>toast.error(getErrorMessage(err))})}else if(parsedId!==null){updateMutation.mutate({id:parsedId,body:values},{onSuccess:()=>{toast.success(t('operations.common.updateSuccess'));navigate(`/operations/assets/${parsedId}`)},onError:(err)=>toast.error(getErrorMessage(err))})}}} /></div>
}
