interface PlaceholderPageProps {
  title: string
  subtitle?: string
}

export function PlaceholderPage({ title, subtitle }: PlaceholderPageProps) {
  return (
    <div className="rounded-xl border border-zinc-200 bg-white p-8 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
      <h1 className="text-xl font-semibold tracking-tight text-zinc-900 dark:text-zinc-50">
        {title}
      </h1>
      {subtitle ? (
        <p className="mt-2 max-w-prose text-sm leading-relaxed text-zinc-600 dark:text-zinc-400">
          {subtitle}
        </p>
      ) : null}
      <p className="mt-6 rounded-lg bg-zinc-50 px-4 py-3 font-mono text-xs text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400">
        CRUD and data wiring will connect here in follow-up tasks.
      </p>
    </div>
  )
}
