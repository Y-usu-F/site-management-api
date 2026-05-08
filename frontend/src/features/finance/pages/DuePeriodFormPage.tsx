import { Link, useNavigate, useParams } from 'react-router-dom'
import { useTranslation } from 'react-i18next'

import { DuePeriodForm } from '@/features/finance/components/DuePeriodForm'
import {
  useCreateDuePeriodMutation,
  useUpdateDuePeriodMutation,
} from '@/features/finance/hooks/useDuePeriodMutations'
import { useDuePeriodQuery } from '@/features/finance/hooks/useDuePeriodQuery'
import { PermissionDeniedNotice } from '@/shared/components/PermissionDeniedNotice'
import { useEffectiveCan } from '@/shared/hooks/useEffectiveCan'
import { useToast } from '@/shared/hooks/useToast'
import { extractValidationErrors, getErrorMessage } from '@/shared/lib/extractValidationErrors'
import { parsePositiveInt } from '@/shared/lib/parseRouteId'

interface Props {
  mode: 'create' | 'edit'
}

export function DuePeriodFormPage({ mode }: Props) {
  const { t } = useTranslation(['finance', 'common'])
  const navigate = useNavigate()
  const { id } = useParams()
  const parsedId = parsePositiveInt(id)
  const canCreate = useEffectiveCan('due_period.create')
  const canUpdate = useEffectiveCan('due_period.update')
  const canAccess = mode === 'create' ? canCreate : canUpdate
  const toast = useToast()
  const createMutation = useCreateDuePeriodMutation()
  const updateMutation = useUpdateDuePeriodMutation()
  const query = useDuePeriodQuery(parsedId ?? 0, mode === 'edit' && parsedId !== null)

  if (!canAccess) {
    return <PermissionDeniedNotice permission={mode === 'create' ? 'due_period.create' : 'due_period.update'} />
  }
  if (mode === 'edit' && parsedId === null) return <div>{t('errorGeneric', { ns: 'common' })}</div>

  const mutation = mode === 'create' ? createMutation : updateMutation
  const serverFieldErrors = extractValidationErrors(mutation.error)

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <h1 className="text-xl font-semibold">
          {mode === 'create' ? `${t('create', { ns: 'common' })} ${t('duePeriods', { ns: 'finance' })}` : `${t('edit', { ns: 'finance' })} ${t('duePeriods', { ns: 'finance' })}`}
        </h1>
        <Link to="/finance/due-periods" className="text-sm text-violet-600">
          {t('back', { ns: 'finance' })}
        </Link>
      </div>
      <DuePeriodForm
        defaultValues={mode === 'edit' ? query.data ?? undefined : undefined}
        submitLabel={mode === 'create' ? t('create', { ns: 'finance' }) : t('save', { ns: 'finance' })}
        isSubmitting={mutation.isPending}
        serverFieldErrors={serverFieldErrors}
        onSubmit={(values) => {
          if (mode === 'create') {
            createMutation.mutate(values, {
              onSuccess: (created) => {
                toast.success(t('createSuccess', { ns: 'finance' }))
                navigate(`/finance/due-periods/${created.id}`)
              },
              onError: (err) => toast.error(getErrorMessage(err)),
            })
            return
          }
          if (parsedId === null) return
          updateMutation.mutate(
            { id: parsedId, body: values },
            {
              onSuccess: () => {
                toast.success(t('updateSuccess', { ns: 'finance' }))
                navigate(`/finance/due-periods/${parsedId}`)
              },
              onError: (err) => toast.error(getErrorMessage(err)),
            },
          )
        }}
      />
    </div>
  )
}
