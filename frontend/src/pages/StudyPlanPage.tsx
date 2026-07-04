import { useEffect, useState } from 'react'
import { apiClient } from '../api/client'
import { useAuth } from '../context/AuthContext'
import type { ResourceRecommendationGroup, StudyPlan } from '../types'

const ACTION_LABEL: Record<string, string> = {
  practice_quiz: 'Practice quiz',
  review_notes: 'Review notes',
  retry_weak_questions: 'Retry weak questions',
}

const TYPE_LABEL: Record<string, string> = {
  video: 'Video',
  pdf: 'PDF',
  notes: 'Notes',
  exercise: 'Exercise',
  mock_paper: 'Mock paper',
  past_paper: 'Past GCE paper',
}

export function StudyPlanPage() {
  const { user } = useAuth()
  const [plan, setPlan] = useState<StudyPlan | null>(null)
  const [recommendations, setRecommendations] = useState<ResourceRecommendationGroup[]>([])
  const [isLoading, setIsLoading] = useState(true)

  useEffect(() => {
    if (!user?.level_id) return
    apiClient
      .get<StudyPlan>('/study-plan', { params: { level_id: user.level_id } })
      // Laravel serializes a null model as {} - treat a plan without items as "no plan yet"
      .then(({ data }) => setPlan(data && data.items ? data : null))
      .finally(() => setIsLoading(false))
    apiClient
      .get<ResourceRecommendationGroup[]>('/study-plan/recommended-resources', { params: { level_id: user.level_id } })
      .then(({ data }) => setRecommendations(data))
  }, [user?.level_id])

  const resourcesByTopic = new Map(recommendations.map((group) => [group.topic_id, group]))

  async function markComplete(itemId: number) {
    await apiClient.post(`/study-plan/items/${itemId}/complete`)
    setPlan((prev) =>
      prev
        ? { ...prev, items: prev.items.map((i) => (i.id === itemId ? { ...i, is_completed: true } : i)) }
        : prev,
    )
  }

  if (isLoading) return <p className="text-sm text-slate-500">Loading study plan...</p>
  if (!plan) return <p className="text-sm text-slate-500">No study plan yet - complete a quiz to generate one.</p>

  return (
    <div className="mx-auto max-w-2xl">
      <h1 className="mb-1 text-2xl font-bold text-slate-900">Your Study Plan</h1>
      <p className="mb-6 text-sm text-slate-500">
        Generated {new Date(plan.generated_at).toLocaleDateString()} - spaced out through {plan.target_date ? new Date(plan.target_date).toLocaleDateString() : '—'}.
      </p>

      <ol className="flex flex-col gap-3">
        {plan.items.map((item) => {
          const resourceGroup = resourcesByTopic.get(item.topic_id)

          return (
            <li
              key={item.id}
              className={`rounded-lg border p-4 ${item.is_completed ? 'border-slate-200 bg-slate-50 opacity-60' : 'border-slate-200 bg-white'}`}
            >
              <div className="flex items-center justify-between">
                <div>
                  <p className="font-medium text-slate-900">{item.topic.name}</p>
                  <p className="text-xs text-slate-500">
                    {ACTION_LABEL[item.recommended_action]} · due {item.due_at ? new Date(item.due_at).toLocaleDateString() : '—'}
                  </p>
                </div>
                {!item.is_completed && (
                  <button
                    onClick={() => markComplete(item.id)}
                    className="rounded-md border border-emerald-600 px-3 py-1.5 text-xs font-semibold text-emerald-700 hover:bg-emerald-50"
                  >
                    Mark done
                  </button>
                )}
                {item.is_completed && <span className="text-xs font-medium text-slate-400">Done</span>}
              </div>

              {resourceGroup && resourceGroup.resources.length > 0 && (
                <div className="mt-3 border-t border-slate-100 pt-3">
                  <p className="mb-2 text-xs font-semibold text-slate-600">Recommended for this topic</p>
                  <ul className="flex flex-col gap-1.5">
                    {resourceGroup.resources.map((resource) => (
                      <li key={resource.id}>
                        <a
                          href={resource.resource_url ?? '#'}
                          target="_blank"
                          rel="noreferrer"
                          className="text-sm text-emerald-700 underline hover:text-emerald-800"
                        >
                          {resource.title}
                        </a>
                        <span className="ml-2 text-xs text-slate-400">{TYPE_LABEL[resource.type] ?? resource.type}</span>
                      </li>
                    ))}
                  </ul>
                </div>
              )}
            </li>
          )
        })}
      </ol>
    </div>
  )
}
