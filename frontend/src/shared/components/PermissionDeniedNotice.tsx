interface PermissionDeniedNoticeProps {
  permission: string
  title?: string
}

export function PermissionDeniedNotice({
  permission,
  title = 'Permission required',
}: PermissionDeniedNoticeProps) {
  return (
    <div className="rounded-xl border border-amber-200 bg-amber-50 p-6 dark:border-amber-900 dark:bg-amber-950/40">
      <h1 className="text-lg font-semibold text-amber-900 dark:text-amber-100">{title}</h1>
      <p className="mt-2 text-sm text-amber-800 dark:text-amber-200">
        You need the{' '}
        <code className="rounded bg-amber-100 px-1 dark:bg-amber-900">{permission}</code> permission.
        Ask an administrator to grant access, or disable strict permission checks in development (
        <code className="rounded bg-amber-100 px-1 dark:bg-amber-900">
          VITE_STRICT_PERMISSIONS=false
        </code>
        ).
      </p>
    </div>
  )
}
