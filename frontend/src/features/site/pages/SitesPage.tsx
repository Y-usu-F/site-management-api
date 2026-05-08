import { useMemo, useState } from 'react'
import { Link } from 'react-router-dom'
import { useTranslation } from 'react-i18next'
import { useMutation, useQueryClient } from '@tanstack/react-query'

import { bulkDeleteSites, downloadSiteTemplate, exportSitesExcel, importSitesExcel } from '@/features/site/api/siteApi'
import { useSitesQuery } from '@/features/site/hooks/useSitesQuery'
import { ApiClientError } from '@/shared/api/types'
import { BulkActionBar } from '@/shared/components/BulkActionBar'
import { ConfirmDialog } from '@/shared/components/ConfirmDialog'
import { EmptyState } from '@/shared/components/EmptyState'
import { ImportExcelDialog } from '@/shared/components/ImportExcelDialog'
import { PermissionDeniedNotice } from '@/shared/components/PermissionDeniedNotice'
import { useToast } from '@/shared/hooks/useToast'
import { formatDateTime } from '@/shared/lib/formatDateTime'
import { getErrorMessage } from '@/shared/lib/extractValidationErrors'
import { useDebouncedValue } from '@/shared/hooks/useDebouncedValue'
import { useEffectiveCan } from '@/shared/hooks/useEffectiveCan'
import { useSelection } from '@/shared/hooks/useSelection'

const SEARCH_DEBOUNCE_MS = 350

export function SitesPage() {
  const { t } = useTranslation(['site', 'common'])
  const toast = useToast()
  const qc = useQueryClient()
  const canList = useEffectiveCan('site.list')
  const canCreate = useEffectiveCan('site.create')
  const canDelete = useEffectiveCan('site.delete')
  const canExport = useEffectiveCan('site.export')
  const canImport = useEffectiveCan('site.import')
  const [searchInput, setSearchInput] = useState('')
  const debouncedSearch = useDebouncedValue(searchInput, SEARCH_DEBOUNCE_MS)
  const [deleteConfirmOpen, setDeleteConfirmOpen] = useState(false)
  const [importOpen, setImportOpen] = useState(false)

  const filterKey =
    debouncedSearch.trim() === '' ? '__all__' : debouncedSearch.trim()
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

  const { data, isPending, isError, error, isFetching } = useSitesQuery(params, {
    enabled: canList,
  })

  const items = data?.items ?? []
  const pageIds = items.map((x) => x.id)
  const { selectedIds, selectedCount, allSelected, toggleOne, toggleAllCurrentPage, clearSelection } =
    useSelection(pageIds)
  const totalPages = data?.total_pages ?? 0
  const total = data?.total ?? 0

  const bulkDeleteMutation = useMutation({
    mutationFn: (ids: number[]) => bulkDeleteSites(ids),
    onSuccess: () => {
      toast.success(t('site.common.bulkDeleteSitesSuccess'))
      clearSelection()
      void qc.invalidateQueries({ queryKey: ['sites'] })
    },
    onError: (err) => {
      if (err instanceof ApiClientError && (err.status === 404 || err.status === 405)) {
        toast.error(t('site.common.bulkDeleteEndpointNotAvailableYet'))
        return
      }
      toast.error(getErrorMessage(err, t('site.common.couldNotDeleteSelectedSites')))
    },
  })

  const exportMutation = useMutation({
    mutationFn: () => exportSitesExcel(params),
    onSuccess: () => toast.success(t('site.common.exportStarted')),
    onError: (err) => {
      if (err instanceof ApiClientError && (err.status === 404 || err.status === 405)) {
        toast.error(t('site.common.exportEndpointNotAvailableYet'))
        return
      }
      toast.error(getErrorMessage(err, t('site.common.couldNotExportSites')))
    },
  })

  const templateMutation = useMutation({
    mutationFn: () => downloadSiteTemplate(),
    onSuccess: () => toast.success(t('site.common.templateDownloaded')),
    onError: (err) => {
      if (err instanceof ApiClientError && (err.status === 404 || err.status === 405)) {
        toast.error(t('site.common.templateEndpointNotAvailableYet'))
        return
      }
      toast.error(getErrorMessage(err, t('site.common.couldNotDownloadTemplate')))
    },
  })

  const importMutation = useMutation({
    mutationFn: (file: File) => importSitesExcel(file),
    onSuccess: (result) => {
      toast.success(
        t('site.common.importDone', {
          inserted: result.inserted_count ?? 0,
          updated: result.updated_count ?? 0,
          skipped: result.skipped_count ?? 0,
        }),
      )
      setImportOpen(false)
      void qc.invalidateQueries({ queryKey: ['sites'] })
    },
    onError: (err) => {
      if (err instanceof ApiClientError && (err.status === 404 || err.status === 405)) {
        toast.error(t('site.common.importEndpointNotAvailableYet'))
        return
      }
      toast.error(getErrorMessage(err, t('site.common.couldNotImportSites')))
    },
  })

  if (!canList) {
    return <PermissionDeniedNotice permission="site.list" />
  }

  return (
    <div className="space-y-6">
      <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
          <h1 className="text-2xl font-semibold tracking-tight text-zinc-900 dark:text-zinc-50">
            {t('site.common.sites')}
          </h1>
          <p className="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
            {total} site(s)
            {debouncedSearch ? ` matching “${debouncedSearch}”` : ''}.
          </p>
        </div>
        <div className="flex flex-col gap-3 sm:flex-row sm:items-center">
          <div className="w-full sm:w-72">
            <label htmlFor="site-search" className="sr-only">
              {t('common.search')}
            </label>
            <input
              id="site-search"
              type="search"
              placeholder={t('common.search')}
              value={searchInput}
              onChange={(e) => setSearchInput(e.target.value)}
              className="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 outline-none ring-violet-500 focus:ring-2 dark:border-zinc-600 dark:bg-zinc-950 dark:text-zinc-50"
            />
          </div>
          {canCreate ? (
            <Link
              to="/sites/new"
              className="inline-flex justify-center rounded-lg bg-violet-600 px-4 py-2 text-sm font-medium text-white hover:bg-violet-700"
            >
              {t('site.common.new')}
            </Link>
          ) : null}
        </div>
      </div>
      <BulkActionBar
        selectedCount={selectedCount}
        canDelete={canDelete}
        canExport={canExport}
        canImport={canImport}
        isBulkDeleting={bulkDeleteMutation.isPending}
        isExporting={exportMutation.isPending || templateMutation.isPending}
        onBulkDelete={() => setDeleteConfirmOpen(true)}
        onExport={() => exportMutation.mutate()}
        onImport={() => setImportOpen(true)}
        onTemplateDownload={() => templateMutation.mutate()}
        onClearSelection={clearSelection}
      />

      {isPending ? (
        <div className="rounded-xl border border-zinc-200 bg-white p-12 text-center text-sm text-zinc-600 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-400">
          {t('site.common.loading')}
        </div>
      ) : null}

      {isError ? (
        <div className="rounded-xl border border-red-200 bg-red-50 p-6 dark:border-red-900 dark:bg-red-950/40">
          <p className="font-medium text-red-900 dark:text-red-100">{t('common.errorGeneric')}</p>
          <p className="mt-2 text-sm text-red-800 dark:text-red-200">
            {error instanceof Error ? error.message : t('common.errorGeneric')}
          </p>
        </div>
      ) : null}

      {!isPending && !isError && items.length === 0 ? (
        <EmptyState
          title={t('common.emptyTitle')}
          description={
            canCreate ? (
              <>
                <Link to="/sites/new" className="text-violet-600 underline">
                  Create a site
                </Link>{' '}
                to get started.
              </>
            ) : (
              'Create permission required to add sites.'
            )
          }
        />
      ) : null}

      {!isPending && !isError && items.length > 0 ? (
        <div className="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
          <div className="overflow-x-auto">
            <table className="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
              <thead className="bg-zinc-50 dark:bg-zinc-800/80">
                <tr>
                  <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500">
                    <input
                      type="checkbox"
                      checked={allSelected}
                      onChange={toggleAllCurrentPage}
                      aria-label="Select all sites on page"
                    />
                  </th>
                  <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500">
                    {t('site.form.code')}
                  </th>
                  <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500">
                    {t('site.form.name')}
                  </th>
                  <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500">
                    {t('site.form.address')}
                  </th>
                  <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500">
                    {t('site.common.status')}
                  </th>
                  <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500">
                    {t('site.common.updated')}
                  </th>
                  <th className="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-zinc-500">
                    {t('site.common.actions')}
                  </th>
                </tr>
              </thead>
              <tbody className="divide-y divide-zinc-200 dark:divide-zinc-700">
                {items.map((site) => (
                  <tr key={site.id} className="hover:bg-zinc-50 dark:hover:bg-zinc-800/40">
                    <td className="px-4 py-3">
                      <input
                        type="checkbox"
                        checked={selectedIds.includes(site.id)}
                        onChange={() => toggleOne(site.id)}
                        aria-label={`Select site ${site.code}`}
                      />
                    </td>
                    <td className="whitespace-nowrap px-4 py-3 font-mono text-sm text-zinc-900 dark:text-zinc-100">
                      {site.code}
                    </td>
                    <td className="px-4 py-3 text-sm text-zinc-900 dark:text-zinc-100">
                      {site.name}
                    </td>
                    <td className="max-w-xs truncate px-4 py-3 text-sm text-zinc-600 dark:text-zinc-400">
                      {site.address?.trim() ? site.address : '—'}
                    </td>
                    <td className="whitespace-nowrap px-4 py-3">
                      <span className="inline-flex rounded-full bg-zinc-100 px-2 py-0.5 text-xs font-medium text-zinc-800 dark:bg-zinc-800 dark:text-zinc-200">
                        {site.status}
                      </span>
                    </td>
                    <td className="whitespace-nowrap px-4 py-3 text-sm text-zinc-600 dark:text-zinc-400">
                      {formatDateTime(site.updated_at ?? site.created_at)}
                    </td>
                    <td className="whitespace-nowrap px-4 py-3 text-right text-sm">
                      <Link
                        to={`/sites/${site.id}`}
                        className="font-medium text-violet-600 hover:underline"
                      >
                        {t('site.common.open')}
                      </Link>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
          {totalPages > 1 ? (
            <div className="flex items-center justify-between border-t border-zinc-200 px-4 py-3 dark:border-zinc-700">
              <span className="text-xs text-zinc-500">
                Page {data?.page ?? page} of {totalPages}
                {isFetching ? ' · Refreshing…' : ''}
              </span>
              <div className="flex gap-2">
                <button
                  type="button"
                  disabled={page <= 1}
                  onClick={() => setPage((p) => Math.max(1, p - 1))}
                  className="rounded-lg border border-zinc-300 px-3 py-1 text-xs font-medium disabled:opacity-40 dark:border-zinc-600"
                >
                  {t('common.pagination.prev')}
                </button>
                <button
                  type="button"
                  disabled={page >= totalPages}
                  onClick={() => setPage((p) => p + 1)}
                  className="rounded-lg border border-zinc-300 px-3 py-1 text-xs font-medium disabled:opacity-40 dark:border-zinc-600"
                >
                  {t('common.pagination.next')}
                </button>
              </div>
            </div>
          ) : null}
        </div>
      ) : null}
      <ConfirmDialog
        isOpen={deleteConfirmOpen}
        title={t('site.common.deleteSelectedSitesTitle')}
        description={t('site.common.deleteSelectedSitesDescription', { count: selectedCount })}
        confirmText={t('site.common.delete')}
        cancelText={t('common.cancel')}
        variant="danger"
        isLoading={bulkDeleteMutation.isPending}
        onClose={() => setDeleteConfirmOpen(false)}
        onConfirm={() => bulkDeleteMutation.mutate(selectedIds)}
      />
      <ImportExcelDialog
        isOpen={importOpen}
        title={t('site.common.importSitesFromExcel')}
        isSubmitting={importMutation.isPending}
        onClose={() => setImportOpen(false)}
        onSubmit={(file) => importMutation.mutate(file)}
      />
    </div>
  )
}
