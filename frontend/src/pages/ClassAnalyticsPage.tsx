import { useEffect, useState } from 'react'
import { useParams } from 'react-router-dom'
import { apiClient } from '../api/client'
import { GroupChat } from '../components/GroupChat'

interface HeatmapRow {
  topic_id: number
  topic_name: string
  average_mastery: number | null
  students_with_data: number
}

interface EngagementRow {
  student_id: number
  student_name: string
  quiz_attempts_completed: number
  last_activity_date: string | null
}

interface StudentIntervention {
  student_id: number
  student_name: string
  average_mastery: number | null
  inactive_days: number | null
  reasons: string[]
  recommended_actions: string[]
}

interface TopicIntervention {
  topic_id: number
  topic_name: string
  average_mastery: number
  students_affected: number
  recommended_action: string
}

interface Analytics {
  class: { id: number; name: string; student_count: number }
  topic_mastery_heatmap: HeatmapRow[]
  engagement: EngagementRow[]
  student_interventions: StudentIntervention[]
  topic_interventions: TopicIntervention[]
}

function masteryColor(score: number | null): string {
  if (score === null) return '#c3c2b7'
  if (score < 50) return '#d03b3b'
  if (score < 70) return '#fab219'
  return '#0ca30c'
}

export function ClassAnalyticsPage() {
  const { classId } = useParams()
  const [analytics, setAnalytics] = useState<Analytics | null>(null)

  useEffect(() => {
    apiClient.get<Analytics>(`/classes/${classId}/analytics`).then(({ data }) => setAnalytics(data))
  }, [classId])

  if (!analytics) return <p className="text-sm text-slate-500">Loading analytics...</p>

  return (
    <div className="mx-auto max-w-3xl">
      <h1 className="mb-1 text-2xl font-bold text-slate-900">{analytics.class.name}</h1>
      <p className="mb-6 text-sm text-slate-500">{analytics.class.student_count} student(s)</p>

      <div className="mb-6">
        <GroupChat kind="classes" id={analytics.class.id} />
      </div>

      <div className="mb-6 rounded-xl border border-slate-200 bg-white p-5">
        <h2 className="mb-4 text-lg font-semibold text-slate-900">Class topic mastery heatmap</h2>
        <div className="flex flex-col gap-3">
          {analytics.topic_mastery_heatmap.map((row) => (
            <div key={row.topic_id} className="flex items-center gap-4">
              <div className="w-56 shrink-0 text-sm font-medium text-slate-700">{row.topic_name}</div>
              <div className="h-3 flex-1 overflow-hidden rounded-full bg-slate-100">
                <div
                  className="h-full rounded-full"
                  style={{ width: `${row.average_mastery ?? 0}%`, backgroundColor: masteryColor(row.average_mastery) }}
                />
              </div>
              <span className="w-32 shrink-0 text-right text-xs font-medium text-slate-500">
                {row.average_mastery !== null ? `${row.average_mastery}%` : 'No data'} ({row.students_with_data})
              </span>
            </div>
          ))}
        </div>
      </div>

      <div className="mb-6 rounded-xl border border-slate-200 bg-white p-5">
        <h2 className="mb-4 text-lg font-semibold text-slate-900">Engagement</h2>
        <table className="w-full text-sm">
          <thead>
            <tr className="border-b border-slate-200 text-left text-xs text-slate-500">
              <th className="pb-2">Student</th>
              <th className="pb-2">Quizzes completed</th>
              <th className="pb-2">Last active</th>
            </tr>
          </thead>
          <tbody>
            {analytics.engagement.map((row) => (
              <tr key={row.student_id} className="border-b border-slate-100">
                <td className="py-2 font-medium text-slate-800">{row.student_name}</td>
                <td className="py-2">{row.quiz_attempts_completed}</td>
                <td className="py-2 text-slate-500">{row.last_activity_date ? new Date(row.last_activity_date).toLocaleDateString() : 'Never'}</td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      {analytics.student_interventions.length > 0 && (
        <div className="mb-6 rounded-xl border border-red-200 bg-red-50 p-5">
          <h2 className="mb-3 text-lg font-semibold text-red-800">Recommended interventions - students</h2>
          <ul className="flex flex-col gap-4">
            {analytics.student_interventions.map((s) => (
              <li key={s.student_id} className="rounded-lg border border-red-200 bg-white p-3">
                <p className="mb-1 font-medium text-slate-900">{s.student_name}</p>
                <ul className="mb-2 list-inside list-disc text-xs text-slate-500">
                  {s.reasons.map((reason, i) => (
                    <li key={i}>{reason}</li>
                  ))}
                </ul>
                <ul className="flex flex-col gap-1">
                  {s.recommended_actions.map((action, i) => (
                    <li key={i} className="text-sm font-medium text-red-700">
                      → {action}
                    </li>
                  ))}
                </ul>
              </li>
            ))}
          </ul>
        </div>
      )}

      {analytics.topic_interventions.length > 0 && (
        <div className="rounded-xl border border-amber-200 bg-amber-50 p-5">
          <h2 className="mb-3 text-lg font-semibold text-amber-800">Recommended interventions - whole class</h2>
          <ul className="flex flex-col gap-2">
            {analytics.topic_interventions.map((t) => (
              <li key={t.topic_id} className="text-sm text-amber-800">
                {t.recommended_action}
              </li>
            ))}
          </ul>
        </div>
      )}
    </div>
  )
}
