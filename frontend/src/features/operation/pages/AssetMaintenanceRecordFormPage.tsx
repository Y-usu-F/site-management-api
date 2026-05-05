import { Link, useNavigate } from 'react-router-dom'
import { AssetMaintenanceRecordForm } from '@/features/operation/components/AssetMaintenanceRecordForm'
import { useCreateAssetMaintenanceRecordMutation } from '@/features/operation/hooks/useAssetMaintenanceRecords'
import { PermissionDeniedNotice } from '@/shared/components/PermissionDeniedNotice'
import { useEffectiveCan } from '@/shared/hooks/useEffectiveCan'
import { useToast } from '@/shared/hooks/useToast'
import { getErrorMessage } from '@/shared/lib/extractValidationErrors'

export function AssetMaintenanceRecordFormPage() {
  const canCreate = useEffectiveCan('asset_maintenance_record.create')
  const mutation = useCreateAssetMaintenanceRecordMutation()
  const navigate = useNavigate()
  const toast = useToast()
  if (!canCreate) return <PermissionDeniedNotice permission="asset_maintenance_record.create" />
  return <div className="space-y-4"><div className="flex items-center justify-between"><h1 className="text-xl font-semibold">New maintenance record</h1><Link to="/operations/asset-maintenance-records" className="text-sm text-violet-600">Back</Link></div><AssetMaintenanceRecordForm isSubmitting={mutation.isPending} submitLabel="Create" onSubmit={(values)=>mutation.mutate(values,{onSuccess:(created)=>{toast.success('Record olusturuldu.');navigate(`/operations/asset-maintenance-records/${created.id}`)},onError:(err)=>toast.error(getErrorMessage(err))})} /></div>
}
