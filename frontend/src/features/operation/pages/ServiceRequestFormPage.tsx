import { Link, useNavigate, useParams } from 'react-router-dom'
import { useTranslation } from 'react-i18next'

import { ServiceRequestForm } from '@/features/operation/components/ServiceRequestForm'
import {
  useCreateServiceRequestMutation,
  useServiceRequestQuery,
  useUpdateServiceRequestMutation,
} from '@/features/operation/hooks/useServiceRequests'
import { PermissionDeniedNotice } from '@/shared/components/PermissionDeniedNotice'
import { useEffectiveCan } from '@/shared/hooks/useEffectiveCan'
import { useToast } from '@/shared/hooks/useToast'
import { extractValidationErrors, getErrorMessage } from '@/shared/lib/extractValidationErrors'
import { parsePositiveInt } from '@/shared/lib/parseRouteId'

interface Props { mode: 'create' | 'edit' }

export function ServiceRequestFormPage({ mode }: Props) {
  const { t } = useTranslation(['operations', 'common'])
  const canCreate = useEffectiveCan('service_request.create')
  const canUpdate = useEffectiveCan('service_request.update')
  const canAccess = mode === 'create' ? canCreate : canUpdate
  const { id } = useParams()
  const parsedId = parsePositiveInt(id)
  const detail = useServiceRequestQuery(parsedId ?? 0, mode === 'edit' && parsedId !== null)
  const createMutation = useCreateServiceRequestMutation()
  const updateMutation = useUpdateServiceRequestMutation()
  const navigate = useNavigate()
  const toast = useToast()
  const mutation = mode === 'create' ? createMutation : updateMutation

  if (!canAccess) return <PermissionDeniedNotice permission={mode === 'create' ? 'service_request.create' : 'service_request.update'} />
  if (mode === 'edit' && parsedId === null) return <div>{t('operations.common.invalidId')}</div>

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between"><h1 className="text-xl font-semibold">{mode === 'create' ? t('operations.common.create') : t('operations.common.edit')} {t('operations.common.serviceRequests')}</h1><Link to="/operations/service-requests" className="text-sm text-violet-600">{t('operations.common.back')}</Link></div>
      <ServiceRequestForm
        defaultValues={mode === 'edit' ? (detail.data as unknown as Record<string, unknown>) : undefined}
        isSubmitting={mutation.isPending}
        submitLabel={mode === 'create' ? t('operations.common.create') : t('operations.common.save')}
        serverFieldErrors={extractValidationErrors(mutation.error)}
        onSubmit={(values) => {
          if (mode === 'create') {
            createMutation.mutate(values, {
              onSuccess: (created) => { toast.success(t('operations.common.createSuccess')); navigate(`/operations/service-requests/${created.id}`) },
              onError: (err) => toast.error(getErrorMessage(err)),
            })
          } else if (parsedId !== null) {
            updateMutation.mutate({ id: parsedId, body: values }, {
              onSuccess: () => { toast.success(t('operations.common.updateSuccess')); navigate(`/operations/service-requests/${parsedId}`) },
              onError: (err) => toast.error(getErrorMessage(err)),
            })
          }
        }}
      />
    </div>
  )
}
