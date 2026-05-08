import { useState } from 'react'
import { useTranslation } from 'react-i18next'

import type { ResidentProfile } from '@/features/resident/types'

const STATUS_OPTIONS = ['active', 'passive'] as const

interface ResidentFormProps {
  defaultValues?: Partial<ResidentProfile>
  submitLabel: string
  isSubmitting: boolean
  serverFieldErrors?: Record<string, string>
  onSubmit: (values: {
    first_name: string
    last_name: string
    identity_number: string
    phone: string
    email: string
    status: string
  }) => void
}

export function ResidentForm({
  defaultValues,
  submitLabel,
  isSubmitting,
  serverFieldErrors = {},
  onSubmit,
}: ResidentFormProps) {
  const { t } = useTranslation(['residents'])
  const [firstName, setFirstName] = useState(defaultValues?.first_name ?? '')
  const [lastName, setLastName] = useState(defaultValues?.last_name ?? '')
  const [identityNumber, setIdentityNumber] = useState(defaultValues?.identity_number ?? '')
  const [phone, setPhone] = useState(defaultValues?.phone ?? '')
  const [email, setEmail] = useState(defaultValues?.email ?? '')
  const [status, setStatus] = useState(defaultValues?.status ?? 'active')
  const [clientErrors, setClientErrors] = useState<Record<string, string>>({})

  const errors = { ...clientErrors, ...serverFieldErrors }

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault()
    const next: Record<string, string> = {}
    if (firstName.trim().length < 2) next.first_name = 'First name en az 2 karakter olmali.'
    if (lastName.trim().length < 2) next.last_name = 'Last name en az 2 karakter olmali.'
    if (email.trim() !== '' && !email.includes('@')) next.email = 'Gecerli bir email giriniz.'
    setClientErrors(next)
    if (Object.keys(next).length > 0) return

    onSubmit({
      first_name: firstName.trim(),
      last_name: lastName.trim(),
      identity_number: identityNumber.trim(),
      phone: phone.trim(),
      email: email.trim(),
      status,
    })
  }

  return (
    <form onSubmit={handleSubmit} className="max-w-2xl space-y-4">
      <div className="grid gap-4 sm:grid-cols-2">
        <div>
          <label htmlFor="resident-first-name" className="block text-sm font-medium">
            {t('form.firstName', { ns: 'residents' })}
          </label>
          <input
            id="resident-first-name"
            value={firstName}
            onChange={(e) => setFirstName(e.target.value)}
            className="mt-1 w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-950"
          />
          {errors.first_name ? <p className="mt-1 text-xs text-red-600">{errors.first_name}</p> : null}
        </div>
        <div>
          <label htmlFor="resident-last-name" className="block text-sm font-medium">
            {t('form.lastName', { ns: 'residents' })}
          </label>
          <input
            id="resident-last-name"
            value={lastName}
            onChange={(e) => setLastName(e.target.value)}
            className="mt-1 w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-950"
          />
          {errors.last_name ? <p className="mt-1 text-xs text-red-600">{errors.last_name}</p> : null}
        </div>
      </div>

      <div className="grid gap-4 sm:grid-cols-2">
        <div>
          <label htmlFor="resident-identity-number" className="block text-sm font-medium">
            {t('form.identityNumber', { ns: 'residents' })}
          </label>
          <input
            id="resident-identity-number"
            value={identityNumber}
            onChange={(e) => setIdentityNumber(e.target.value)}
            className="mt-1 w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-950"
          />
          {errors.identity_number ? (
            <p className="mt-1 text-xs text-red-600">{errors.identity_number}</p>
          ) : null}
        </div>
        <div>
          <label htmlFor="resident-phone" className="block text-sm font-medium">
            {t('form.phone', { ns: 'residents' })}
          </label>
          <input
            id="resident-phone"
            value={phone}
            onChange={(e) => setPhone(e.target.value)}
            className="mt-1 w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-950"
          />
          {errors.phone ? <p className="mt-1 text-xs text-red-600">{errors.phone}</p> : null}
        </div>
      </div>

      <div className="grid gap-4 sm:grid-cols-2">
        <div>
          <label htmlFor="resident-email" className="block text-sm font-medium">
            {t('form.email', { ns: 'residents' })}
          </label>
          <input
            id="resident-email"
            value={email}
            onChange={(e) => setEmail(e.target.value)}
            className="mt-1 w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-950"
          />
          {errors.email ? <p className="mt-1 text-xs text-red-600">{errors.email}</p> : null}
        </div>
        <div>
          <label htmlFor="resident-status" className="block text-sm font-medium">
            {t('form.status', { ns: 'residents' })}
          </label>
          <select
            id="resident-status"
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
      </div>

      <button
        type="submit"
        disabled={isSubmitting}
        className="rounded-lg bg-violet-600 px-4 py-2 text-sm font-medium text-white disabled:opacity-50"
      >
        {isSubmitting ? t('saving', { ns: 'residents' }) : submitLabel}
      </button>
    </form>
  )
}
