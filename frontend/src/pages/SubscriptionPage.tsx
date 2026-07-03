import { useEffect, useState } from 'react'
import { apiClient, apiErrorMessage } from '../api/client'
import type { Subscription, SubscriptionPlan } from '../types'

const FEATURE_LABELS: { key: keyof SubscriptionPlan['features']; label: string }[] = [
  { key: 'topic_quizzes_per_day', label: 'Topic quizzes per day' },
  { key: 'gce_simulations_per_month', label: 'GCE simulations per month' },
  { key: 'study_plan', label: 'Personalized study plan' },
  { key: 'parent_email', label: 'Weekly parent email reports' },
  { key: 'peer_groups', label: 'Peer groups & challenges' },
  { key: 'teacher_analytics', label: 'Teacher analytics access' },
  { key: 'paper_marking', label: 'Paper marking (Premium+)' },
  { key: 'ai_coach_messages_per_day', label: 'AI academic coach messages per day' },
]

function formatFeature(value: number | boolean | null): string {
  if (typeof value === 'boolean') return value ? 'Included' : 'Not included'
  return value === null ? 'Unlimited' : String(value)
}

export function SubscriptionPage() {
  const [plans, setPlans] = useState<SubscriptionPlan[]>([])
  const [current, setCurrent] = useState<Subscription | null>(null)
  const [error, setError] = useState<string | null>(null)
  const [subscribing, setSubscribing] = useState<string | null>(null)

  function load() {
    apiClient.get<SubscriptionPlan[]>('/subscription-plans').then(({ data }) => setPlans(data))
    apiClient.get<Subscription>('/subscription').then(({ data }) => setCurrent(data))
  }

  useEffect(load, [])

  async function subscribe(planCode: string) {
    setError(null)
    setSubscribing(planCode)
    try {
      await apiClient.post('/subscription/subscribe', { plan_code: planCode })
      load()
    } catch (err) {
      setError(apiErrorMessage(err))
    } finally {
      setSubscribing(null)
    }
  }

  return (
    <div className="mx-auto max-w-4xl">
      <h1 className="mb-1 text-2xl font-bold text-slate-900">Subscription</h1>
      <p className="mb-6 text-sm text-slate-500">
        Current plan: <span className="font-semibold text-emerald-700">{current?.plan.name ?? '—'}</span>
      </p>

      {error && <p className="mb-4 text-sm text-red-600">{error}</p>}

      <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
        {plans.map((plan) => {
          const isCurrent = current?.plan.code === plan.code
          return (
            <div key={plan.id} className={`rounded-xl border p-5 ${isCurrent ? 'border-emerald-500 ring-1 ring-emerald-500' : 'border-slate-200'} bg-white`}>
              <h2 className="text-lg font-bold text-slate-900">{plan.name}</h2>
              <p className="mb-4 text-2xl font-bold text-slate-900">
                {plan.price_xaf === 0 ? 'Free' : `${plan.price_xaf.toLocaleString()} XAF`}
                {plan.price_xaf > 0 && <span className="text-sm font-normal text-slate-500">/mo</span>}
              </p>

              <ul className="mb-5 flex flex-col gap-1.5 text-xs text-slate-600">
                {FEATURE_LABELS.map(({ key, label }) => (
                  <li key={key} className="flex justify-between">
                    <span>{label}</span>
                    <span className="font-medium text-slate-800">{formatFeature(plan.features[key])}</span>
                  </li>
                ))}
              </ul>

              <button
                disabled={isCurrent || subscribing === plan.code}
                onClick={() => subscribe(plan.code)}
                className="w-full rounded-md bg-emerald-600 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-700 disabled:opacity-40"
              >
                {isCurrent ? 'Current plan' : subscribing === plan.code ? 'Subscribing...' : 'Choose plan'}
              </button>
            </div>
          )
        })}
      </div>
    </div>
  )
}
