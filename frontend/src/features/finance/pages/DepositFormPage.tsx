import { useQuery } from '@tanstack/react-query'
import { Link, useNavigate } from 'react-router-dom'

import { listLookupResidents, listLookupUnits } from '@/features/finance/api/lookupsApi'
import { DepositForm } from '@/features/finance/components/DepositForm'
import { useCreateDepositMutation } from '@/features/finance/hooks/useDepositMutations'
import { PermissionDeniedNotice } from '@/shared/components/PermissionDeniedNotice'
import { useEffectiveCan } from '@/shared/hooks/useEffectiveCan'
import { useToast } from '@/shared/hooks/useToast'
import { extractValidationErrors, getErrorMessage } from '@/shared/lib/extractValidationErrors'

export function DepositFormPage() {
  const canCreate = useEffectiveCan('deposit.create')
  const navigate = useNavigate()
  const toast = useToast()
  const mutation = useCreateDepositMutation()
  const residents = useQuery({ queryKey: ['finance', 'lookup', 'residents'], queryFn: listLookupResidents })
  const units = useQuery({ queryKey: ['finance', 'lookup', 'units'], queryFn: listLookupUnits })

  if (!canCreate) return <PermissionDeniedNotice permission="deposit.create" />

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <h1 className="text-xl font-semibold">New deposit</h1>
        <Link to="/finance/deposits" className="text-sm text-violet-600">
          Back
        </Link>
      </div>
      <DepositForm
        residents={residents.data ?? []}
        units={units.data ?? []}
        submitLabel="Create deposit"
        isSubmitting={mutation.isPending}
        serverFieldErrors={extractValidationErrors(mutation.error)}
        onSubmit={(values) => {
          mutation.mutate(values, {
            onSuccess: (created) => {
              toast.success('Deposit olusturuldu.')
              navigate(`/finance/deposits/${created.id}`)
            },
            onError: (err) => toast.error(getErrorMessage(err)),
          })
        }}
      />
    </div>
  )
}
