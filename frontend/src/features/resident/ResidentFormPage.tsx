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
        {t('residents.common.invalidResidentId')}. <Link to="/residents">{t('common.back')}</Link>
      </p>
    )
  }

  if (mode === 'edit' && isPending) {
    return <p className="text-sm text-zinc-600">{t('residents.common.loading')}</p>
  }

  if (mode === 'edit' && !existing) {
    return (
      <p className="text-sm text-red-600">
        {t('residents.common.notFound')}. <Link to="/residents">{t('common.back')}</Link>
      </p>
    )
  }

  const isSubmitting = createMut.isPending || updateMut.isPending
  const mutationError = createMut.error ?? updateMut.error

  return (
    <div className="space-y-6">
      <nav className="text-xs text-zinc-500">
        <Link to="/residents" className="hover:text-violet-600">
          {t('residents.common.residents')}
        </Link>
        {mode === 'edit' && existing ? (
          <>
            <span className="mx-1">/</span>
            <Link to={`/residents/${existing.id}`} className="hover:text-violet-600">
              {existing.first_name} {existing.last_name}
            </Link>
            <span className="mx-1">/</span>
            <span>{t('residents.common.edit')}</span>
          </>
        ) : (
          <>
            <span className="mx-1">/</span>
            <span>{t('residents.common.new')}</span>
          </>
        )}
      </nav>

      <h1 className="text-2xl font-semibold text-zinc-900 dark:text-zinc-50">
        {mode === 'create' ? t('residents.form.createResident') : t('residents.common.edit')}
      </h1>

      {mutationError ? (
        <div className="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800 dark:border-red-900 dark:bg-red-950/40 dark:text-red-200">
          <p>{getErrorMessage(mutationError, 'Request failed')}</p>
        </div>
      ) : null}

      <ResidentForm
        defaultValues={existing ?? undefined}
        submitLabel={mode === 'create' ? t('residents.form.createResident') : t('residents.form.saveChanges')}
        isSubmitting={isSubmitting}
        serverFieldErrors={serverErrors}
        onSubmit={(values) => {
          setServerErrors({})
          if (mode === 'create') {
            createMut.mutate(values, {
              onSuccess: (created) => {
                toast.success('Resident created.')
                navigate(`/residents/${created.id}`)
              },
              onError: (err) => {
                setServerErrors(extractValidationErrors(err))
                toast.error(getErrorMessage(err, 'Could not create resident.'))
              },
            })
          } else if (id !== null) {
            updateMut.mutate(
              { id, body: values },
              {
                onSuccess: (updated) => {
                  toast.success('Resident updated.')
                  navigate(`/residents/${updated.id}`)
                },
                onError: (err) => {
                  setServerErrors(extractValidationErrors(err))
                  toast.error(getErrorMessage(err, 'Could not update resident.'))
                },
              },
            )
          }
        }}
      />
    </div>
  )
}
