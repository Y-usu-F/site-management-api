import { useMemo, useState } from 'react'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { Link, useParams } from 'react-router-dom'
import { useTranslation } from 'react-i18next'

import {
  createResidentContact,
  deleteResidentContact,
  listResidentContacts,
  updateResidentContact,
} from '@/features/resident/api/contactApi'
import type { ContactPayload, ResidentContact } from '@/features/resident/types'
import { ConfirmDialog } from '@/shared/components/ConfirmDialog'
import { EmptyState } from '@/shared/components/EmptyState'
import { PermissionDeniedNotice } from '@/shared/components/PermissionDeniedNotice'
import { useEffectiveCan } from '@/shared/hooks/useEffectiveCan'
import { useToast } from '@/shared/hooks/useToast'
import { extractValidationErrors, getErrorMessage } from '@/shared/lib/extractValidationErrors'
import { parsePositiveInt } from '@/shared/lib/parseRouteId'

function emptyPayload(residentId: number): ContactPayload {
  return {
    resident_profile_id: residentId,
    type: 'phone',
    label: '',
    value: '',
    is_primary: false,
  }
}

export function ResidentContactsPage() {
  const { t } = useTranslation(['residents', 'common'])
  const { residentId: raw } = useParams<{ residentId: string }>()
  const residentId = parsePositiveInt(raw)
  const toast = useToast()
  const qc = useQueryClient()

  const canList = useEffectiveCan('resident_contact.list')
  const canCreate = useEffectiveCan('resident_contact.create')
  const canUpdate = useEffectiveCan('resident_contact.update')
  const canDelete = useEffectiveCan('resident_contact.delete')

  const [form, setForm] = useState<ContactPayload>(() => emptyPayload(residentId ?? 0))
  const [editId, setEditId] = useState<number | null>(null)
  const [confirmDeleteId, setConfirmDeleteId] = useState<number | null>(null)
  const [serverErrors, setServerErrors] = useState<Record<string, string>>({})

  const params = useMemo(
    () => ({ page: 1, per_page: 100, resident_profile_id: residentId ?? 0 }),
    [residentId],
  )

  const contactsQ = useQuery({
    queryKey: ['resident-contacts', params],
    queryFn: () => listResidentContacts(params),
    enabled: canList && residentId !== null,
  })

  const createMut = useMutation({
    mutationFn: createResidentContact,
    onSuccess: () => {
      toast.success(t('residents.common.contactCreated'))
      setServerErrors({})
      setForm(emptyPayload(residentId ?? 0))
      void qc.invalidateQueries({ queryKey: ['resident-contacts'] })
    },
    onError: (err) => {
      setServerErrors(extractValidationErrors(err))
      toast.error(getErrorMessage(err, t('residents.common.contactCreateFailed')))
    },
  })

  const updateMut = useMutation({
    mutationFn: ({ id, body }: { id: number; body: Partial<ContactPayload> }) =>
      updateResidentContact(id, body),
    onSuccess: () => {
      toast.success(t('residents.common.contactUpdated'))
      setEditId(null)
      setServerErrors({})
      setForm(emptyPayload(residentId ?? 0))
      void qc.invalidateQueries({ queryKey: ['resident-contacts'] })
    },
    onError: (err) => {
      setServerErrors(extractValidationErrors(err))
      toast.error(getErrorMessage(err, t('residents.common.contactUpdateFailed')))
    },
  })

  const deleteMut = useMutation({
    mutationFn: deleteResidentContact,
    onSuccess: () => {
      toast.success(t('residents.common.contactDeleted'))
      setConfirmDeleteId(null)
      void qc.invalidateQueries({ queryKey: ['resident-contacts'] })
    },
    onError: (err) => toast.error(getErrorMessage(err, t('residents.common.contactDeleteFailed'))),
  })

  if (!canList) return <PermissionDeniedNotice permission="resident_contact.list" />
  if (residentId === null) return <p className="text-sm text-zinc-600">{t('residents.common.invalidResidentId')}</p>

  const rows = contactsQ.data?.items ?? []
  const isSubmitting = createMut.isPending || updateMut.isPending

  const loadForEdit = (row: ResidentContact) => {
    setEditId(row.id)
    setServerErrors({})
    setForm({
      resident_profile_id: row.resident_profile_id,
      type: row.type,
      label: row.label ?? '',
      value: row.value,
      is_primary: Boolean(Number(row.is_primary)),
    })
  }

  return (
    <div className="space-y-6">
      <nav className="text-xs text-zinc-500">
        <Link to={`/residents/${residentId}`} className="hover:text-violet-600">
          {t('residents.common.resident')} {residentId}
        </Link>
        <span className="mx-1">/</span>
        <span>{t('residents.common.contacts')}</span>
      </nav>
      <h1 className="text-2xl font-semibold text-zinc-900 dark:text-zinc-50">{t('residents.common.contacts')}</h1>

      {(canCreate || (canUpdate && editId !== null)) && (
        <form
          className="grid gap-4 rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900 sm:grid-cols-2"
          onSubmit={(e) => {
            e.preventDefault()
            const payload: ContactPayload = {
              ...form,
              resident_profile_id: residentId,
              label: form.label?.trim() ? form.label : null,
              value: form.value.trim(),
            }
            if (editId === null) createMut.mutate(payload)
            else updateMut.mutate({ id: editId, body: payload })
          }}
        >
          <div>
            <label className="block text-sm font-medium">{t('residents.common.type')}</label>
            <select
              value={form.type}
              onChange={(e) => setForm((p) => ({ ...p, type: e.target.value }))}
              className="mt-1 w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-950"
            >
              <option value="phone">phone</option>
              <option value="email">email</option>
              <option value="emergency">emergency</option>
            </select>
          </div>
          <div>
            <label className="block text-sm font-medium">{t('residents.common.label')}</label>
            <input
              value={form.label ?? ''}
              onChange={(e) => setForm((p) => ({ ...p, label: e.target.value }))}
              className="mt-1 w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-950"
            />
          </div>
          <div className="sm:col-span-2">
            <label className="block text-sm font-medium">{t('residents.common.value')}</label>
            <input
              value={form.value}
              onChange={(e) => setForm((p) => ({ ...p, value: e.target.value }))}
              className="mt-1 w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-950"
            />
            {serverErrors.value ? <p className="mt-1 text-xs text-red-600">{serverErrors.value}</p> : null}
          </div>
          <label className="inline-flex items-center gap-2 text-sm sm:col-span-2">
            <input
              type="checkbox"
              checked={Boolean(form.is_primary)}
              onChange={(e) => setForm((p) => ({ ...p, is_primary: e.target.checked }))}
            />
            {t('residents.common.primary')}
          </label>
          <div className="sm:col-span-2 flex gap-2">
            <button
              type="submit"
              disabled={isSubmitting}
              className="rounded-lg bg-violet-600 px-4 py-2 text-sm font-medium text-white disabled:opacity-50"
            >
              {isSubmitting ? t('residents.common.saving') : editId === null ? t('residents.common.createContact') : t('residents.common.save')}
            </button>
            {editId !== null ? (
              <button
                type="button"
                className="rounded-lg border px-4 py-2 text-sm dark:border-zinc-700"
                onClick={() => {
                  setEditId(null)
                  setServerErrors({})
                  setForm(emptyPayload(residentId))
                }}
              >
                {t('residents.common.cancelEdit')}
              </button>
            ) : null}
          </div>
        </form>
      )}

      {!contactsQ.isPending && !contactsQ.isError && rows.length === 0 ? (
        <EmptyState title={t('common.emptyTitle')} description={t('common.emptyDescription')} />
      ) : null}

      {rows.length > 0 ? (
        <div className="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
          <div className="overflow-x-auto">
            <table className="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
              <thead className="bg-zinc-50 dark:bg-zinc-800/80">
                <tr>
                  <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500">{t('residents.common.type')}</th>
                  <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500">{t('residents.common.label')}</th>
                  <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500">{t('residents.common.value')}</th>
                  <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500">{t('residents.common.primary')}</th>
                  <th className="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-zinc-500">{t('residents.common.actions')}</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-zinc-200 dark:divide-zinc-700">
                {rows.map((row) => (
                  <tr key={row.id}>
                    <td className="px-4 py-3 text-sm">{row.type}</td>
                    <td className="px-4 py-3 text-sm">{row.label?.trim() ? row.label : '—'}</td>
                    <td className="px-4 py-3 text-sm">{row.value}</td>
                    <td className="px-4 py-3 text-sm">{Number(row.is_primary) === 1 ? t('residents.common.yes') : t('residents.common.no')}</td>
                    <td className="px-4 py-3 text-right text-sm">
                      <div className="flex justify-end gap-3">
                        {canUpdate ? (
                          <button type="button" className="text-violet-600 hover:underline" onClick={() => loadForEdit(row)}>
                            {t('common.edit')}
                          </button>
                        ) : null}
                        {canDelete ? (
                          <button type="button" className="text-red-600 hover:underline" onClick={() => setConfirmDeleteId(row.id)}>
                            {t('common.delete')}
                          </button>
                        ) : null}
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      ) : null}

      <ConfirmDialog
        isOpen={confirmDeleteId !== null}
        title={t('residents.common.deleteContactTitle')}
        description={t('residents.common.deleteContactDescription')}
        confirmText={t('common.delete')}
        cancelText={t('common.cancel')}
        variant="danger"
        isLoading={deleteMut.isPending}
        onClose={() => setConfirmDeleteId(null)}
        onConfirm={() => {
          if (confirmDeleteId === null) return
          deleteMut.mutate(confirmDeleteId)
        }}
      />
    </div>
  )
}
