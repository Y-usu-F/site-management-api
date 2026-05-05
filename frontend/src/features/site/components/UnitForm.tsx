import { useState } from 'react'

import type { Unit } from '@/features/site/types'

const STATUS_OPTIONS = ['active', 'passive'] as const

function emptyToApiNumber(raw: string): string | null {
  const t = raw.trim()
  if (t === '') return null
  return t
}

interface UnitFormProps {
  siteId: number
  blockId: number
  floorId: number
  defaultValues?: Partial<Unit>
  submitLabel: string
  isSubmitting: boolean
  serverFieldErrors?: Record<string, string>
  onSubmit: (values: {
    site_id: number
    block_id: number
    floor_id: number
    unit_no: string
    type: string
    gross_area: string | null
    net_area: string | null
    land_share: string | null
    occupant_name: string
    status: string
  }) => void
}

export function UnitForm({
  siteId,
  blockId,
  floorId,
  defaultValues,
  submitLabel,
  isSubmitting,
  serverFieldErrors = {},
  onSubmit,
}: UnitFormProps) {
  const [unitNo, setUnitNo] = useState(defaultValues?.unit_no ?? '')
  const [type, setType] = useState(defaultValues?.type ?? '')
  const [grossArea, setGrossArea] = useState(
    defaultValues?.gross_area !== undefined && defaultValues?.gross_area !== null
      ? String(defaultValues.gross_area)
      : '',
  )
  const [netArea, setNetArea] = useState(
    defaultValues?.net_area !== undefined && defaultValues?.net_area !== null
      ? String(defaultValues.net_area)
      : '',
  )
  const [landShare, setLandShare] = useState(
    defaultValues?.land_share !== undefined && defaultValues?.land_share !== null
      ? String(defaultValues.land_share)
      : '',
  )
  const [occupantName, setOccupantName] = useState(defaultValues?.occupant_name ?? '')
  const [status, setStatus] = useState(defaultValues?.status ?? 'active')
  const [clientErrors, setClientErrors] = useState<Record<string, string>>({})
  const errors = { ...clientErrors, ...serverFieldErrors }

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault()
    const next: Record<string, string> = {}
    if (unitNo.trim().length < 1) next.unit_no = 'Unit number is required.'
    setClientErrors(next)
    if (Object.keys(next).length > 0) return
    onSubmit({
      site_id: siteId,
      block_id: blockId,
      floor_id: floorId,
      unit_no: unitNo.trim(),
      type: type.trim(),
      gross_area: emptyToApiNumber(grossArea),
      net_area: emptyToApiNumber(netArea),
      land_share: emptyToApiNumber(landShare),
      occupant_name: occupantName.trim(),
      status,
    })
  }

  return (
    <form onSubmit={handleSubmit} className="max-w-xl space-y-4">
      <div>
        <label htmlFor="unit-no" className="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
          Unit number
        </label>
        <input
          id="unit-no"
          value={unitNo}
          onChange={(e) => setUnitNo(e.target.value)}
          className="mt-1 w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-950"
        />
        {errors.unit_no ? <p className="mt-1 text-xs text-red-600">{errors.unit_no}</p> : null}
      </div>
      <div>
        <label htmlFor="unit-type" className="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
          Type
        </label>
        <input
          id="unit-type"
          value={type}
          onChange={(e) => setType(e.target.value)}
          placeholder="Apartment, shop, …"
          className="mt-1 w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-950"
        />
        {errors.type ? <p className="mt-1 text-xs text-red-600">{errors.type}</p> : null}
      </div>
      <div className="grid gap-4 sm:grid-cols-3">
        <div>
          <label
            htmlFor="unit-gross"
            className="block text-sm font-medium text-zinc-700 dark:text-zinc-300"
          >
            Gross area
          </label>
          <input
            id="unit-gross"
            value={grossArea}
            onChange={(e) => setGrossArea(e.target.value)}
            className="mt-1 w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-950"
          />
          {errors.gross_area ? (
            <p className="mt-1 text-xs text-red-600">{errors.gross_area}</p>
          ) : null}
        </div>
        <div>
          <label htmlFor="unit-net" className="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
            Net area
          </label>
          <input
            id="unit-net"
            value={netArea}
            onChange={(e) => setNetArea(e.target.value)}
            className="mt-1 w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-950"
          />
          {errors.net_area ? <p className="mt-1 text-xs text-red-600">{errors.net_area}</p> : null}
        </div>
        <div>
          <label
            htmlFor="unit-land"
            className="block text-sm font-medium text-zinc-700 dark:text-zinc-300"
          >
            Land share
          </label>
          <input
            id="unit-land"
            value={landShare}
            onChange={(e) => setLandShare(e.target.value)}
            className="mt-1 w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-950"
          />
          {errors.land_share ? (
            <p className="mt-1 text-xs text-red-600">{errors.land_share}</p>
          ) : null}
        </div>
      </div>
      <div>
        <label
          htmlFor="unit-occupant"
          className="block text-sm font-medium text-zinc-700 dark:text-zinc-300"
        >
          Occupant name
        </label>
        <input
          id="unit-occupant"
          value={occupantName}
          onChange={(e) => setOccupantName(e.target.value)}
          className="mt-1 w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-950"
        />
        {errors.occupant_name ? (
          <p className="mt-1 text-xs text-red-600">{errors.occupant_name}</p>
        ) : null}
      </div>
      <div>
        <label htmlFor="unit-status" className="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
          Status
        </label>
        <select
          id="unit-status"
          value={status}
          onChange={(e) => setStatus(e.target.value)}
          className="mt-1 w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-950"
        >
          {STATUS_OPTIONS.map((s) => (
            <option key={s} value={s}>
              {s}
            </option>
          ))}
        </select>
        {errors.status ? <p className="mt-1 text-xs text-red-600">{errors.status}</p> : null}
      </div>
      <div>
        <button
          type="submit"
          disabled={isSubmitting}
          className="rounded-lg bg-violet-600 px-4 py-2 text-sm font-medium text-white disabled:opacity-50"
        >
          {isSubmitting ? 'Saving…' : submitLabel}
        </button>
      </div>
    </form>
  )
}
