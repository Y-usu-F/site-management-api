import { useState } from 'react'

interface ImportExcelDialogProps {
  isOpen: boolean
  title: string
  isSubmitting?: boolean
  onClose: () => void
  onSubmit: (file: File) => void
}

export function ImportExcelDialog({
  isOpen,
  title,
  isSubmitting = false,
  onClose,
  onSubmit,
}: ImportExcelDialogProps) {
  const [file, setFile] = useState<File | null>(null)

  if (!isOpen) return null

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/45 p-4">
      <div className="w-full max-w-md rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
        <h2 className="text-lg font-semibold text-zinc-900 dark:text-zinc-100">{title}</h2>
        <p className="mt-1 text-sm text-zinc-600 dark:text-zinc-300">
          Please select an <code>.xlsx</code> file.
        </p>
        <input
          type="file"
          accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
          onChange={(e) => setFile(e.target.files?.[0] ?? null)}
          className="mt-4 block w-full text-sm"
        />
        <div className="mt-6 flex justify-end gap-2">
          <button
            type="button"
            onClick={onClose}
            disabled={isSubmitting}
            className="rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-600"
          >
            Cancel
          </button>
          <button
            type="button"
            onClick={() => file && onSubmit(file)}
            disabled={!file || isSubmitting}
            className="rounded-lg bg-violet-600 px-3 py-2 text-sm text-white disabled:opacity-60"
          >
            {isSubmitting ? 'Importing…' : 'Import'}
          </button>
        </div>
      </div>
    </div>
  )
}
