import { useTranslation } from 'react-i18next'

interface ConfirmDialogProps {
  isOpen: boolean
  title: string
  description: string
  confirmText: string
  cancelText: string
  variant: 'danger' | 'default'
  isLoading?: boolean
  onClose: () => void
  onConfirm: () => void
}

export function ConfirmDialog({
  isOpen,
  title,
  description,
  confirmText,
  cancelText,
  variant,
  isLoading = false,
  onClose,
  onConfirm,
}: ConfirmDialogProps) {
  const { t } = useTranslation(['common'])
  if (!isOpen) return null

  const confirmClass =
    variant === 'danger'
      ? 'bg-red-600 text-white hover:bg-red-700'
      : 'bg-violet-600 text-white hover:bg-violet-700'

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/45 p-4">
      <div className="w-full max-w-md rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
        <h2 className="text-lg font-semibold text-zinc-900 dark:text-zinc-100">{title}</h2>
        <p className="mt-2 text-sm text-zinc-600 dark:text-zinc-300">{description}</p>
        <div className="mt-6 flex justify-end gap-2">
          <button
            type="button"
            onClick={onClose}
            disabled={isLoading}
            className="rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-600"
          >
            {cancelText}
          </button>
          <button
            type="button"
            onClick={onConfirm}
            disabled={isLoading}
            className={`rounded-lg px-3 py-2 text-sm disabled:opacity-60 ${confirmClass}`}
          >
            {isLoading ? t('common.pleaseWait') : confirmText}
          </button>
        </div>
      </div>
    </div>
  )
}
