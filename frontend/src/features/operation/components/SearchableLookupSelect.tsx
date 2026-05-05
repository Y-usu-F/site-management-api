import { useMemo, useState } from 'react'
import { useQuery } from '@tanstack/react-query'

import type { LookupOption } from '@/features/operation/api/lookupsApi'
import { useDebouncedValue } from '@/shared/hooks/useDebouncedValue'

interface SearchableLookupSelectProps {
  label: string
  placeholder: string
  value: string
  onChange: (value: string) => void
  queryKey: string
  queryFn: (search?: string) => Promise<LookupOption[]>
}

export function SearchableLookupSelect({
  label,
  placeholder,
  value,
  onChange,
  queryKey,
  queryFn,
}: SearchableLookupSelectProps) {
  const [search, setSearch] = useState('')
  const debouncedSearch = useDebouncedValue(search, 300)
  const lookupQuery = useQuery({
    queryKey: ['operation', 'lookup', queryKey, debouncedSearch],
    queryFn: () => queryFn(debouncedSearch),
  })

  const selectedLabel = useMemo(() => {
    const selected = (lookupQuery.data ?? []).find((option) => String(option.id) === value)
    return selected?.label ?? ''
  }, [lookupQuery.data, value])

  return (
    <div className="space-y-1">
      <span className="block text-xs text-zinc-600 dark:text-zinc-300">{label}</span>
      <input
        value={search}
        onChange={(event) => setSearch(event.target.value)}
        placeholder={`${placeholder} ara`}
        className="w-full rounded border px-3 py-2 text-sm"
      />
      <select
        value={value}
        onChange={(event) => onChange(event.target.value)}
        className="w-full rounded border px-3 py-2 text-sm"
      >
        <option value="">{placeholder}</option>
        {(lookupQuery.data ?? []).map((option) => (
          <option key={option.id} value={option.id}>
            {option.label}
          </option>
        ))}
      </select>
      {value && selectedLabel ? <p className="text-xs text-zinc-500">Secili: {selectedLabel}</p> : null}
    </div>
  )
}

