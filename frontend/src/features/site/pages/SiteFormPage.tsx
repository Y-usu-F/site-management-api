import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { Link, useNavigate, useParams } from 'react-router-dom'

import { SiteForm } from '@/features/site/components/SiteForm'
import { useSiteQuery } from '@/features/site/hooks/useSiteQuery'
import { useCreateSiteMutation, useUpdateSiteMutation } from '@/features/site/hooks/useSiteMutations'
import { PermissionDeniedNotice } from '@/shared/components/PermissionDeniedNotice'
import { useToast } from '@/shared/hooks/useToast'
import { extractValidationErrors, getErrorMessage } from '@/shared/lib/extractValidationErrors'
import { parsePositiveInt } from '@/shared/lib/parseRouteId'
import { useEffectiveCan } from '@/shared/hooks/useEffectiveCan'

export function SiteFormPage({ mode }: { mode: 'create' | 'edit' }) {
  const { t } = useTranslation(['site', 'common'])
  const { id: idParam } = useParams<{ id: string }>()
  const id = mode === 'edit' ? parsePositiveInt(idParam) : null
  const navigate = useNavigate()
  const [serverErrors, setServerErrors] = useState<Record<string, string>>({})
  const toast = useToast()

  const canCreate = useEffectiveCan('site.create')
  const canUpdate = useEffectiveCan('site.update')

  const allowed = mode === 'create' ? canCreate : canUpdate
  const { data: existing, isPending } = useSiteQuery(id ?? 0, {
    enabled: mode === 'edit' && id !== null && allowed,
  })

  const createMut = useCreateSiteMutation()
  const updateMut = useUpdateSiteMutation()

  if (!allowed) {
    return (
      <PermissionDeniedNotice permission={mode === 'create' ? 'site.create' : 'site.update'} />
    )
  }

  if (mode === 'edit' && id === null) {
    return (
      <p className="text-sm text-zinc-600">
        {t('invalidId', { ns: 'site' })}. <Link to="/sites">{t('back', { ns: 'common' })}</Link>
      </p>
    )
  }

  if (mode === 'edit' && isPending) {
    return <p className="text-sm text-zinc-600">{t('loading', { ns: 'site' })}</p>
  }

  if (mode === 'edit' && !existing) {
    return (
      <p className="text-sm text-red-600">
        {t('notFound', { ns: 'site' })}. <Link to="/sites">{t('back', { ns: 'common' })}</Link>
      </p>
    )
  }

  const isSubmitting = createMut.isPending || updateMut.isPending
  const mutationError = createMut.error ?? updateMut.error

  return (
    <div className="space-y-6">
      <nav className="text-xs text-zinc-500">
        <Link to="/sites" className="hover:text-violet-600">
          {t('sites', { ns: 'site' })}
        </Link>
        {mode === 'edit' && existing ? (
          <>
            <span className="mx-1">/</span>
            <Link to={`/sites/${existing.id}`} className="hover:text-violet-600">
              {existing.code}
            </Link>
            <span className="mx-1">/</span>
            <span>{t('edit', { ns: 'site' })}</span>
          </>
        ) : (
          <>
            <span className="mx-1">/</span>
            <span>{t('new', { ns: 'site' })}</span>
          </>
        )}
      </nav>
      <h1 className="text-2xl font-semibold text-zinc-900 dark:text-zinc-50">
        {mode === 'create' ? `${t('new', { ns: 'site' })} ${t('sites', { ns: 'site' })}` : `${t('edit', { ns: 'site' })} ${t('sites', { ns: 'site' })}`}
      </h1>

      {mutationError ? (
        <div className="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800 dark:border-red-900 dark:bg-red-950/40 dark:text-red-200">
          <p>{getErrorMessage(mutationError, t('requestFailed', { ns: 'site' }))}</p>
        </div>
      ) : null}

      <SiteForm
        defaultValues={existing ?? undefined}
        submitLabel={mode === 'create' ? t('create', { ns: 'common' }) : t('saveChanges', { ns: 'site' })}
        isSubmitting={isSubmitting}
        serverFieldErrors={serverErrors}
        onSubmit={(values) => {
          setServerErrors({})
          if (mode === 'create') {
            createMut.mutate(values, {
              onSuccess: (created) => {
                toast.success(`${t('sites', { ns: 'site' })} ${t('create', { ns: 'common' })}`)
                navigate(`/sites/${created.id}`)
              },
              onError: (err) => {
                setServerErrors(extractValidationErrors(err))
                toast.error(getErrorMessage(err, t('errorGeneric', { ns: 'common' })))
              },
            })
          } else if (id !== null) {
            updateMut.mutate(
              { id, body: values },
              {
                onSuccess: (updated) => {
                  toast.success(`${t('sites', { ns: 'site' })} ${t('update', { ns: 'common' })}`)
                  navigate(`/sites/${updated.id}`)
                },
                onError: (err) => {
                  setServerErrors(extractValidationErrors(err))
                  toast.error(getErrorMessage(err, t('errorGeneric', { ns: 'common' })))
                },
              },
            )
          }
        }}
      />
    </div>
  )
}
