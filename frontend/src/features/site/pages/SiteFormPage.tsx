import { useState } from 'react'
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
        Invalid id. <Link to="/sites">Back</Link>
      </p>
    )
  }

  if (mode === 'edit' && isPending) {
    return <p className="text-sm text-zinc-600">Loading…</p>
  }

  if (mode === 'edit' && !existing) {
    return (
      <p className="text-sm text-red-600">
        Site not found. <Link to="/sites">Back</Link>
      </p>
    )
  }

  const isSubmitting = createMut.isPending || updateMut.isPending
  const mutationError = createMut.error ?? updateMut.error

  return (
    <div className="space-y-6">
      <nav className="text-xs text-zinc-500">
        <Link to="/sites" className="hover:text-violet-600">
          Sites
        </Link>
        {mode === 'edit' && existing ? (
          <>
            <span className="mx-1">/</span>
            <Link to={`/sites/${existing.id}`} className="hover:text-violet-600">
              {existing.code}
            </Link>
            <span className="mx-1">/</span>
            <span>Edit</span>
          </>
        ) : (
          <>
            <span className="mx-1">/</span>
            <span>New</span>
          </>
        )}
      </nav>
      <h1 className="text-2xl font-semibold text-zinc-900 dark:text-zinc-50">
        {mode === 'create' ? 'New site' : 'Edit site'}
      </h1>

      {mutationError ? (
        <div className="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800 dark:border-red-900 dark:bg-red-950/40 dark:text-red-200">
          <p>{getErrorMessage(mutationError, 'Request failed')}</p>
        </div>
      ) : null}

      <SiteForm
        defaultValues={existing ?? undefined}
        submitLabel={mode === 'create' ? 'Create site' : 'Save changes'}
        isSubmitting={isSubmitting}
        serverFieldErrors={serverErrors}
        onSubmit={(values) => {
          setServerErrors({})
          if (mode === 'create') {
            createMut.mutate(values, {
              onSuccess: (created) => {
                toast.success('Site created.')
                navigate(`/sites/${created.id}`)
              },
              onError: (err) => {
                setServerErrors(extractValidationErrors(err))
                toast.error(getErrorMessage(err, 'Could not create site.'))
              },
            })
          } else if (id !== null) {
            updateMut.mutate(
              { id, body: values },
              {
                onSuccess: (updated) => {
                  toast.success('Site updated.')
                  navigate(`/sites/${updated.id}`)
                },
                onError: (err) => {
                  setServerErrors(extractValidationErrors(err))
                  toast.error(getErrorMessage(err, 'Could not update site.'))
                },
              },
            )
          }
        }}
      />
    </div>
  )
}
