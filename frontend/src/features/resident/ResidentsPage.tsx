import { useMemo, useState } from 'react'
import { Link } from 'react-router-dom'
import { useTranslation } from 'react-i18next'

import { useDeleteResidentMutation } from '@/features/resident/hooks/useResidentMutations'
import { useResidentsQuery } from '@/features/resident/hooks/useResidentsQuery'
import { ConfirmDialog } from '@/shared/components/ConfirmDialog'
import { EmptyState } from '@/shared/components/EmptyState'
import { PermissionDeniedNotice } from '@/shared/components/PermissionDeniedNotice'
import { useDebouncedValue } from '@/shared/hooks/useDebouncedValue'
import { useEffectiveCan } from '@/shared/hooks/useEffectiveCan'
import { useToast } from '@/shared/hooks/useToast'
import { formatDateTime } from '@/shared/lib/formatDateTime'
import { getErrorMessage } from '@/shared/lib/extractValidationErrors'

const SEARCH_DEBOUNCE_MS = 350

export function ResidentsPage() {
  const { t } = useTranslation(['residents', 'common'])
  const toast = useToast()
  const canList = useEffectiveCan('resident.list')
  const canCreate = useEffectiveCan('resident.create')
  const canDelete = useEffectiveCan('resident.delete')

  const [searchInput, setSearchInput] = useState('')
  const debouncedSearch = useDebouncedValue(searchInput, SEARCH_DEBOUNCE_MS)
  const [selectedDeleteId, setSelectedDeleteId] = useState<number | null>(null)

  const filterKey = debouncedSearch.trim() === '' ? '__all__' : debouncedSearch.trim()
  const [pagesByFilter, setPagesByFilter] = useState<Record<string, number>>({})
  const page = pagesByFilter[filterKey] ?? 1
  const setPage = (next: number | ((prev: number) => number)) => {
    setPagesByFilter((prev) => {
      const current = prev[filterKey] ?? 1
      const resolved = typeof next === 'function' ? next(current) : next
      return { ...prev, [filterKey]: resolved }
    })
  }

  const params = useMemo(
    () => ({
      page,
      per_page: 20,
      search: debouncedSearch.trim() === '' ? undefined : debouncedSearch.trim(),
    }),
    [page, debouncedSearch],
  )

  const { data, isPending, isError, error, isFetching } = useResidentsQuery(params, canList)
  const deleteMutation = useDeleteResidentMutation()

  if (!canList) {
    return <PermissionDeniedNotice permission="resident.list" />
  }

  const residents = data?.items ?? []
  const total = data?.total ?? 0
  const totalPages = data?.total_pages ?? 0
  const deletingResident = residents.find((x) => x.id === selectedDeleteId) ?? null
  const statusLabel = (value: string) => {
    if (value === 'active') return t('active', { ns: 'residents' })
    if (value === 'passive') return t('passive', { ns: 'residents' })
    return value
  }

  return (
    <div className="space-y-6">
      <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
          <h1 className="text-2xl font-semibold tracking-tight text-zinc-900 dark:text-zinc-50">
            {t('residents', { ns: 'residents' })}
          </h1>
          <p className="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
            {total} {t('resident', { ns: 'residents' })}
            {debouncedSearch ? ` ${t('matching', { ns: 'residents', query: debouncedSearch })}` : ''}.
          </p>
        </div>
        <div className="flex flex-col gap-3 sm:flex-row sm:items-center">
          <input
            type="search"
            placeholder={t('search', { ns: 'common' })}
            value={searchInput}
            onChange={(e) => setSearchInput(e.target.value)}
            className="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 outline-none ring-violet-500 focus:ring-2 sm:w-72 dark:border-zinc-600 dark:bg-zinc-950 dark:text-zinc-50"
          />
          {canCreate ? (
            <Link
              to="/residents/new"
              className="inline-flex justify-center rounded-lg bg-violet-600 px-4 py-2 text-sm font-medium text-white hover:bg-violet-700"
            >
              {t('form.createResident', { ns: 'residents' })}
            </Link>
          ) : null}
        </div>
      </div>

      {isPending ? (
        <div className="rounded-xl border border-zinc-200 bg-white p-12 text-center text-sm text-zinc-600 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-400">
          {t('loading', { ns: 'residents' })}
        </div>
      ) : null}

      {isError ? (
        <div className="rounded-xl border border-red-200 bg-red-50 p-6 dark:border-red-900 dark:bg-red-950/40">
          <p className="font-medium text-red-900 dark:text-red-100">{t('errorGeneric', { ns: 'common' })}</p>
          <p className="mt-2 text-sm text-red-800 dark:text-red-200">
            {error instanceof Error ? error.message : t('errorGeneric', { ns: 'common' })}
          </p>
        </div>
      ) : null}

      {!isPending && !isError && residents.length === 0 ? (
        <EmptyState
          title={t('emptyTitle', { ns: 'common' })}
          description={
            canCreate ? (
              <>
                <Link to="/residents/new" className="text-violet-600 underline">
                  {t('createResidentCta', { ns: 'residents' })}
                </Link>{' '}
                {t('getStarted', { ns: 'residents' })}
              </>
            ) : (
              t('createPermissionRequiredToAddResidents', { ns: 'residents' })
            )
          }
        />
      ) : null}

      {!isPending && !isError && residents.length > 0 ? (
        <div className="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
          <div className="overflow-x-auto">
            <table className="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
              <thead className="bg-zinc-50 dark:bg-zinc-800/80">
                <tr>
                  <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500">
                    {t('form.firstName', { ns: 'residents' })}
                  </th>
                  <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500">
                    {t('identityNumber', { ns: 'residents' })}
                  </th>
                  <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500">
                    {t('phone', { ns: 'residents' })}
                  </th>
                  <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500">
                    {t('email', { ns: 'residents' })}
                  </th>
                  <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500">
                    {t('status', { ns: 'residents' })}
                  </th>
                  <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500">
                    {t('updated', { ns: 'residents' })}
                  </th>
                  <th className="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-zinc-500">
                    {t('actions', { ns: 'residents' })}
                  </th>
                </tr>
              </thead>
              <tbody className="divide-y divide-zinc-200 dark:divide-zinc-700">
                {residents.map((resident) => (
                  <tr key={resident.id} className="hover:bg-zinc-50 dark:hover:bg-zinc-800/40">
                    <td className="px-4 py-3 text-sm text-zinc-900 dark:text-zinc-100">
                      {resident.first_name} {resident.last_name}
                    </td>
                    <td className="px-4 py-3 text-sm text-zinc-600 dark:text-zinc-400">
                      {resident.identity_number?.trim() ? resident.identity_number : '—'}
                    </td>
                    <td className="px-4 py-3 text-sm text-zinc-600 dark:text-zinc-400">
                      {resident.phone?.trim() ? resident.phone : '—'}
                    </td>
                    <td className="px-4 py-3 text-sm text-zinc-600 dark:text-zinc-400">
                      {resident.email?.trim() ? resident.email : '—'}
                    </td>
                    <td className="px-4 py-3 text-sm">{statusLabel(resident.status)}</td>
                    <td className="px-4 py-3 text-sm text-zinc-600 dark:text-zinc-400">
                      {formatDateTime(resident.updated_at ?? resident.created_at)}
                    </td>
                    <td className="px-4 py-3 text-right text-sm">
                      <div className="flex justify-end gap-3">
                        <Link to={`/residents/${resident.id}`} className="font-medium text-violet-600 hover:underline">
                          {t('open', { ns: 'residents' })}
                        </Link>
                        {canDelete ? (
                          <button
                            type="button"
                            onClick={() => setSelectedDeleteId(resident.id)}
                            className="font-medium text-red-600 hover:underline"
                          >
                            {t('delete', { ns: 'residents' })}
                          </button>
                        ) : null}
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
          {totalPages > 1 ? (
            <div className="flex items-center justify-between border-t border-zinc-200 px-4 py-3 dark:border-zinc-700">
              <span className="text-xs text-zinc-500">
                {t('pagination.page', { ns: 'common' })} {data?.page ?? page} {t('pagination.of', { ns: 'common' })} {totalPages}
                {isFetching ? ` · ${t('pagination.refreshing', { ns: 'common' })}` : ''}
              </span>
              <div className="flex gap-2">
                <button
                  type="button"
                  disabled={page <= 1}
                  onClick={() => setPage((p) => Math.max(1, p - 1))}
                  className="rounded-lg border border-zinc-300 px-3 py-1 text-xs font-medium disabled:opacity-40 dark:border-zinc-600"
                >
                  {t('pagination.prev', { ns: 'common' })}
                </button>
                <button
                  type="button"
                  disabled={page >= totalPages}
                  onClick={() => setPage((p) => p + 1)}
                  className="rounded-lg border border-zinc-300 px-3 py-1 text-xs font-medium disabled:opacity-40 dark:border-zinc-600"
                >
                  {t('pagination.next', { ns: 'common' })}
                </button>
              </div>
            </div>
          ) : null}
        </div>
      ) : null}

      <ConfirmDialog
        isOpen={selectedDeleteId !== null}
        title={t('deleteResidentTitle', { ns: 'residents' })}
        description={
          deletingResident
            ? t('deleteResidentDescriptionNamed', { ns: 'residents', defaultValue: {
                name: `${deletingResident.first_name} ${deletingResident.last_name}`,
              } })
            : t('deleteResidentDescriptionGeneric', { ns: 'residents' })
        }
        confirmText={t('delete', { ns: 'residents' })}
        cancelText={t('cancel', { ns: 'common' })}
        variant="danger"
        isLoading={deleteMutation.isPending}
        onClose={() => setSelectedDeleteId(null)}
        onConfirm={() => {
          if (selectedDeleteId === null) return
          deleteMutation.mutate(selectedDeleteId, {
            onSuccess: () => {
              toast.success(t('residentDeleted', { ns: 'residents' }))
              setSelectedDeleteId(null)
            },
            onError: (err) => toast.error(getErrorMessage(err, t('residentDeleteFailed', { ns: 'residents' }))),
          })
        }}
      />
    </div>
  )
}
