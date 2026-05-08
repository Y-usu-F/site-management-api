import { useState } from 'react'
import { Link, useNavigate, useParams } from 'react-router-dom'
import { useTranslation } from 'react-i18next'

import { ResidentForm } from '@/features/resident/components/ResidentForm'
import { useCreateResidentMutation, useUpdateResidentMutation } from '@/features/resident/hooks/useResidentMutations'
import { useResidentQuery } from '@/features/resident/hooks/useResidentQuery'
import { PermissionDeniedNotice } from '@/shared/components/PermissionDeniedNotice'
import { useEffectiveCan } from '@/shared/hooks/useEffectiveCan'
import { useToast } from '@/shared/hooks/useToast'
import { extractValidationErrors, getErrorMessage } from '@/shared/lib/extractValidationErrors'
import { parsePositiveInt } from '@/shared/lib/parseRouteId'

export function ResidentFormPage({ mode }: { mode: 'create' | 'edit' }) {
  const { t } = useTranslation(['residents', 'common'])
  const { id: idParam } = useParams<{ id: string }>()
  const id = mode === 'edit' ? parsePositiveInt(idParam) : null
  const navigate = useNavigate()
  const toast = useToast()
  const [serverErrors, setServerErrors] = useState<Record<string, string>>({})

  const canCreate = useEffectiveCan('resident.create')
  const canUpdate = useEffectiveCan('resident.update')
  const allowed = mode === 'create' ? canCreate : canUpdate

  const { data: existing, isPending } = useResidentQuery(id ?? 0, mode === 'edit' && id !== null && allowed)
  const createMut = useCreateResidentMutation()
  const updateMut = useUpdateResidentMutation()

  if (!allowed) {
    return (
      <PermissionDeniedNotice
        permission={mode === 'create' ? 'resident.create' : 'resident.update'}
      />
    )
  }

  if (mode === 'edit' && id === null) {
    return (
      <p className="text-sm text-zinc-600">
        {t('invalidResidentId', { ns: 'residents' })}. <Link to="/residents">{t('back', { ns: 'common' })}</Link>
      </p>
    )
  }

  if (mode === 'edit' && isPending) {
    return <p className="text-sm text-zinc-600">{t('loading', { ns: 'residents' })}</p>
  }

  if (mode === 'edit' && !existing) {
    return (
      <p className="text-sm text-red-600">
        {t('notFound', { ns: 'residents' })}. <Link to="/residents">{t('back', { ns: 'common' })}</Link>
      </p>
    )
  }

  const isSubmitting = createMut.isPending || updateMut.isPending
  const mutationError = createMut.error ?? updateMut.error

  return (
    <div className="space-y-6">
      <nav className="text-xs text-zinc-500">
        <Link to="/residents" className="hover:text-violet-600">
          {t('residents', { ns: 'residents' })}
        </Link>
        {mode === 'edit' && existing ? (
          <>
            <span className="mx-1">/</span>
            <Link to={`/residents/${existing.id}`} className="hover:text-violet-600">
              {existing.first_name} {existing.last_name}
            </Link>
            <span className="mx-1">/</span>
            <span>{t('edit', { ns: 'residents' })}</span>
          </>
        ) : (
          <>
            <span className="mx-1">/</span>
            <span>{t('new', { ns: 'residents' })}</span>
          </>
        )}
      </nav>

      <h1 className="text-2xl font-semibold text-zinc-900 dark:text-zinc-50">
        {mode === 'create' ? t('form.createResident', { ns: 'residents' }) : t('edit', { ns: 'residents' })}
      </h1>

      {mutationError ? (
        <div className="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800 dark:border-red-900 dark:bg-red-950/40 dark:text-red-200">
          <p>{getErrorMessage(mutationError, t('requestFailed', { ns: 'residents' }))}</p>
        </div>
      ) : null}

      <ResidentForm
        defaultValues={existing ?? undefined}
        submitLabel={mode === 'create' ? t('form.createResident', { ns: 'residents' }) : t('form.saveChanges', { ns: 'residents' })}
        isSubmitting={isSubmitting}
        serverFieldErrors={serverErrors}
        onSubmit={(values) => {
          setServerErrors({})
          if (mode === 'create') {
            createMut.mutate(values, {
              onSuccess: (created) => {
                toast.success(t('residentCreated', { ns: 'residents' }))
                navigate(`/residents/${created.id}`)
              },
              onError: (err) => {
                setServerErrors(extractValidationErrors(err))
                toast.error(getErrorMessage(err, t('residentCreateFailed', { ns: 'residents' })))
              },
            })
          } else if (id !== null) {
            updateMut.mutate(
              { id, body: values },
              {
                onSuccess: (updated) => {
                  toast.success(t('residentUpdated', { ns: 'residents' }))
                  navigate(`/residents/${updated.id}`)
                },
                onError: (err) => {
                  setServerErrors(extractValidationErrors(err))
                  toast.error(getErrorMessage(err, t('residentUpdateFailed', { ns: 'residents' })))
                },
              },
            )
          }
        }}
      />
    </div>
  )
}
