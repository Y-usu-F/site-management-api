import { useTranslation } from 'react-i18next'

interface BulkActionBarProps {
  selectedCount: number
  canDelete: boolean
  canExport: boolean
  canImport: boolean
  isBulkDeleting?: boolean
  isExporting?: boolean
  onBulkDelete: () => void
  onExport: () => void
  onImport: () => void
  onTemplateDownload: () => void
  onClearSelection: () => void
}

export function BulkActionBar({
  selectedCount,
  canDelete,
  canExport,
  canImport,
  isBulkDeleting = false,
  isExporting = false,
  onBulkDelete,
  onExport,
  onImport,
  onTemplateDownload,
  onClearSelection,
}: BulkActionBarProps) {
  const { t } = useTranslation(['common'])
  return (
    <div className="flex flex-wrap items-center gap-2 rounded-lg border border-zinc-200 bg-zinc-50 p-3 dark:border-zinc-700 dark:bg-zinc-900/60">
      <span className="text-sm text-zinc-600 dark:text-zinc-300">{t('bulk.selected', { ns: 'common', count: selectedCount })}</span>
      <button
        type="button"
        onClick={onClearSelection}
        disabled={selectedCount === 0}
        className="rounded border border-zinc-300 px-3 py-1 text-xs disabled:opacity-50 dark:border-zinc-600"
      >
        {t('bulk.clear', { ns: 'common' })}
      </button>
      {canDelete ? (
        <button
          type="button"
          onClick={onBulkDelete}
          disabled={selectedCount === 0 || isBulkDeleting}
          className="rounded border border-red-300 px-3 py-1 text-xs text-red-700 disabled:opacity-50 dark:border-red-800"
        >
          {isBulkDeleting ? t('bulk.deleting', { ns: 'common' }) : t('bulk.bulkDelete', { ns: 'common' })}
        </button>
      ) : null}
      {canExport ? (
        <button
          type="button"
          onClick={onExport}
          disabled={isExporting}
          className="rounded border border-zinc-300 px-3 py-1 text-xs disabled:opacity-50 dark:border-zinc-600"
        >
          {isExporting ? t('bulk.exporting', { ns: 'common' }) : t('bulk.exportExcel', { ns: 'common' })}
        </button>
      ) : null}
      {canImport ? (
        <>
          <button
            type="button"
            onClick={onImport}
            className="rounded border border-zinc-300 px-3 py-1 text-xs dark:border-zinc-600"
          >
            {t('bulk.importExcel', { ns: 'common' })}
          </button>
          <button
            type="button"
            onClick={onTemplateDownload}
            className="rounded border border-zinc-300 px-3 py-1 text-xs dark:border-zinc-600"
          >
            {t('bulk.downloadTemplate', { ns: 'common' })}
          </button>
        </>
      ) : null}
    </div>
  )
}
