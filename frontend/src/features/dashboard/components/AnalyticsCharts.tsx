import type { ReactNode } from 'react'
import {
  Bar,
  BarChart,
  CartesianGrid,
  Line,
  LineChart,
  ResponsiveContainer,
  Tooltip,
  XAxis,
  YAxis,
} from 'recharts'

import type { DashboardAnalytics } from '@/features/analytics/types'

interface AnalyticsChartsProps {
  analytics: DashboardAnalytics
  labels: {
    paymentsTrend: string
    serviceRequestsTrend: string
    serviceRequestDistribution: string
    workOrderDistribution: string
    totalAxis: string
    countAxis: string
  }
}

function ChartCard({ title, children }: { title: string; children: ReactNode }) {
  return (
    <div className="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
      <h2 className="mb-3 text-sm font-medium text-zinc-600 dark:text-zinc-300">{title}</h2>
      <div className="h-64">{children}</div>
    </div>
  )
}

export function AnalyticsCharts({ analytics, labels }: AnalyticsChartsProps) {
  return (
    <div className="grid gap-4 lg:grid-cols-2">
      <ChartCard title={labels.paymentsTrend}>
        <ResponsiveContainer width="100%" height="100%">
          <LineChart data={analytics.trends.payments_last_30_days}>
            <CartesianGrid strokeDasharray="3 3" />
            <XAxis dataKey="date" tick={{ fontSize: 11 }} />
            <YAxis tick={{ fontSize: 11 }} />
            <Tooltip />
            <Line type="monotone" dataKey="total" stroke="#7c3aed" strokeWidth={2} dot={false} />
          </LineChart>
        </ResponsiveContainer>
      </ChartCard>

      <ChartCard title={labels.serviceRequestsTrend}>
        <ResponsiveContainer width="100%" height="100%">
          <BarChart data={analytics.trends.service_requests_last_30_days}>
            <CartesianGrid strokeDasharray="3 3" />
            <XAxis dataKey="date" tick={{ fontSize: 11 }} />
            <YAxis tick={{ fontSize: 11 }} />
            <Tooltip />
            <Bar dataKey="count" fill="#2563eb" />
          </BarChart>
        </ResponsiveContainer>
      </ChartCard>

      <ChartCard title={labels.serviceRequestDistribution}>
        <ResponsiveContainer width="100%" height="100%">
          <BarChart data={analytics.distributions.service_request_statuses}>
            <CartesianGrid strokeDasharray="3 3" />
            <XAxis dataKey="status" tick={{ fontSize: 11 }} />
            <YAxis tick={{ fontSize: 11 }} />
            <Tooltip />
            <Bar dataKey="count" fill="#0d9488" />
          </BarChart>
        </ResponsiveContainer>
      </ChartCard>

      <ChartCard title={labels.workOrderDistribution}>
        <ResponsiveContainer width="100%" height="100%">
          <BarChart data={analytics.distributions.work_order_statuses}>
            <CartesianGrid strokeDasharray="3 3" />
            <XAxis dataKey="status" tick={{ fontSize: 11 }} />
            <YAxis tick={{ fontSize: 11 }} />
            <Tooltip />
            <Bar dataKey="count" fill="#ea580c" />
          </BarChart>
        </ResponsiveContainer>
      </ChartCard>
    </div>
  )
}
