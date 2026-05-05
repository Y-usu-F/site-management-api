import { Link, useNavigate, useParams } from 'react-router-dom'

import { DueDefinitionForm } from '@/features/finance/components/DueDefinitionForm'
import {
  useCreateDueDefinitionMutation,
  useUpdateDueDefinitionMutation,
} from '@/features/finance/hooks/useDueDefinitionMutations'
import { useDueDefinitionQuery } from '@/features/finance/hooks/useDueDefinitionQuery'
import { PermissionDeniedNotice } from '@/shared/components/PermissionDeniedNotice'
import { useEffectiveCan } from '@/shared/hooks/useEffectiveCan'
import { useToast } from '@/shared/hooks/useToast'
import { extractValidationErrors, getErrorMessage } from '@/shared/lib/extractValidationErrors'
import { parsePositiveInt } from '@/shared/lib/parseRouteId'

interface Props {
  mode: 'create' | 'edit'
}

export function DueDefinitionFormPage({ mode }: Props) {
  const navigate = useNavigate()
  const { id } = useParams()
  const parsedId = parsePositiveInt(id)
  const canCreate = useEffectiveCan('due_definition.create')
  const canUpdate = useEffectiveCan('due_definition.update')
  const canAccess = mode === 'create' ? canCreate : canUpdate
  const toast = useToast()
  const createMutation = useCreateDueDefinitionMutation()
  const updateMutation = useUpdateDueDefinitionMutation()
  const query = useDueDefinitionQuery(parsedId ?? 0, mode === 'edit' && parsedId !== null)

  if (!canAccess) {
    return <PermissionDeniedNotice permission={mode === 'create' ? 'due_definition.create' : 'due_definition.update'} />
  }
  if (mode === 'edit' && parsedId === null) return <div>Gecersiz ID.</div>

  const mutation = mode === 'create' ? createMutation : updateMutation
  const serverFieldErrors = extractValidationErrors(mutation.error)

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <h1 className="text-xl font-semibold">
          {mode === 'create' ? 'New due definition' : 'Edit due definition'}
        </h1>
        <Link to="/finance/due-definitions" className="text-sm text-violet-600">
          Back
        </Link>
      </div>
      <DueDefinitionForm
        defaultValues={mode === 'edit' ? query.data ?? undefined : undefined}
        submitLabel={mode === 'create' ? 'Create' : 'Save'}
        isSubmitting={mutation.isPending}
        serverFieldErrors={serverFieldErrors}
        onSubmit={(values) => {
          if (mode === 'create') {
            createMutation.mutate(values, {
              onSuccess: (created) => {
                toast.success('Due definition olusturuldu.')
                navigate(`/finance/due-definitions/${created.id}`)
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
                toast.success('Due definition guncellendi.')
                navigate(`/finance/due-definitions/${parsedId}`)
              },
              onError: (err) => toast.error(getErrorMessage(err)),
            },
          )
        }}
      />
    </div>
  )
}
