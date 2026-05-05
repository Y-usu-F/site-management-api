import { useMemo, useState } from 'react'

export function useSelection(pageIds: number[]) {
  const [selectedIds, setSelectedIds] = useState<number[]>([])

  const pageIdSet = useMemo(() => new Set(pageIds), [pageIds])

  const allSelected = pageIds.length > 0 && pageIds.every((id) => selectedIds.includes(id))
  const selectedCount = selectedIds.length

  function toggleOne(id: number) {
    setSelectedIds((prev) => (prev.includes(id) ? prev.filter((x) => x !== id) : [...prev, id]))
  }

  function toggleAllCurrentPage() {
    setSelectedIds((prev) =>
      allSelected
        ? prev.filter((id) => !pageIdSet.has(id))
        : [...new Set([...prev.filter((id) => pageIdSet.has(id)), ...pageIds])],
    )
  }

  function clearSelection() {
    setSelectedIds([])
  }

  return {
    selectedIds,
    selectedCount,
    allSelected,
    toggleOne,
    toggleAllCurrentPage,
    clearSelection,
  }
}
