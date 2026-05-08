import { useQuery } from '@tanstack/react-query'
import { useTranslation } from 'react-i18next'
import { Link, useNavigate } from 'react-router-dom'

import { listLookupResidents, listLookupUnits } from '@/features/finance/api/lookupsApi'
import { PaymentForm } from '@/features/finance/components/PaymentForm'
import { useCreateManualPaymentMutation } from '@/features/finance/hooks/usePaymentMutations'
import { PermissionDeniedNotice } from '@/shared/components/PermissionDeniedNotice'
import { useEffectiveCan } from '@/shared/hooks/useEffectiveCan'
import { useToast } from '@/shared/hooks/useToast'
import { extractValidationErrors, getErrorMessage } from '@/shared/lib/extractValidationErrors'

export function PaymentFormPage() {
  const { t } = useTranslation(['finance', 'common'])
  const canCreate = useEffectiveCan('payment.create_manual')
  const navigate = useNavigate()
  const toast = useToast()
  const mutation = useCreateManualPaymentMutation()
  const residents = useQuery({ queryKey: ['finance', 'lookup', 'residents'], queryFn: listLookupResidents })
  const units = useQuery({ queryKey: ['finance', 'lookup', 'units'], queryFn: listLookupUnits })

  if (!canCreate) return <PermissionDeniedNotice permission="payment.create_manual" />

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <h1 className="text-xl font-semibold">{t('finance.common.newPayment')}</h1>
        <Link to="/finance/payments" className="text-sm text-violet-600">
          {t('finance.common.back')}
        </Link>
      </div>
      <PaymentForm
        residents={residents.data ?? []}
        units={units.data ?? []}
        submitLabel={t('finance.common.create')}
        isSubmitting={mutation.isPending}
        serverFieldErrors={extractValidationErrors(mutation.error)}
        onSubmit={(values) => {
          mutation.mutate(values, {
            onSuccess: (created) => {
              toast.success(t('finance.common.createSuccess'))
              navigate(`/finance/payments/${created.id}`)
            },
            onError: (err) => toast.error(getErrorMessage(err)),
          })
        }}
      />
    </div>
  )
}
