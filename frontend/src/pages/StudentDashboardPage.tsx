import { useEffect, useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { apiClient, apiErrorMessage } from '../api/client'
import { useAuth } from '../context/AuthContext'
import type { GamificationSummary, Level, Topic, TopicMasteryRow } from '../types'

const MASTERY_COLOR = (score: number) => {
  if (score < 50) return { bar: '#d03b3b', label: 'Weak' } // critical
  if (score < 70) return { bar: '#fab219', label: 'Developing' } // warning
  return { bar: '#0ca30c', label: 'Strong' } // good
}

export function StudentDashboardPage() {
  const navigate = useNavigate()
  const { user } = useAuth()
  const levelId = user?.level_id ?? null
  const [levelName, setLevelName] = useState<string | null>(null)
  const [topics, setTopics] = useState<Topic[]>([])
  const [mastery, setMastery] = useState<TopicMasteryRow[]>([])
  const [gamification, setGamification] = useState<GamificationSummary | null>(null)
  const [error, setError] = useState<string | null>(null)
  const [startingTopicId, setStartingTopicId] = useState<number | null>(null)
  const [startingGce, setStartingGce] = useState(false)

  useEffect(() => {
    apiClient.get<GamificationSummary>('/gamification/summary').then(({ data }) => setGamification(data))
  }, [])

  useEffect(() => {
    if (!levelId) return
    apiClient.get<Level[]>('/levels').then(({ data }) => setLevelName(data.find((l) => l.id === levelId)?.name ?? null))
    apiClient.get<Topic[]>('/topics', { params: { level_id: levelId } }).then(({ data }) => setTopics(data))
    apiClient.get<TopicMasteryRow[]>('/mastery', { params: { level_id: levelId } }).then(({ data }) => setMastery(data))
  }, [levelId])

  const masteryByTopic = new Map(mastery.map((m) => [m.topic_id, m]))

  async function startTopicPractice(topicId: number) {
    setError(null)
    setStartingTopicId(topicId)
    try {
      const { data } = await apiClient.post('/quizzes/topic-practice', { topic_id: topicId })
      navigate(`/quiz/${data.attempt.id}`, { state: { attempt: data.attempt, question: data.question } })
    } catch (err) {
      setError(apiErrorMessage(err))
    } finally {
      setStartingTopicId(null)
    }
  }

  async function startGceSimulation() {
    if (!levelId) return
    setError(null)
    setStartingGce(true)
    try {
      const { data } = await apiClient.post('/quizzes/gce-simulation', { level_id: levelId })
      navigate(`/quiz/${data.attempt.id}`, { state: { attempt: data.attempt, questions: data.questions } })
    } catch (err) {
      setError(apiErrorMessage(err))
    } finally {
      setStartingGce(false)
    }
  }

  if (!levelId) {
    return (
      <p className="rounded-md bg-amber-50 px-4 py-3 text-sm text-amber-800">
        Your account has no exam level set. Please contact support to have this fixed.
      </p>
    )
  }

  return (
    <div className="flex flex-col gap-6">
      <div className="flex flex-wrap items-center justify-between gap-4">
        <h1 className="text-2xl font-bold text-slate-900">Your Dashboard</h1>
        {levelName && <span className="rounded-md bg-slate-100 px-3 py-1.5 text-sm font-medium text-slate-600">{levelName}</span>}
      </div>

      {error && <p className="rounded-md bg-red-50 px-4 py-2 text-sm text-red-700">{error}</p>}

      <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <StatTile label="Points" value={gamification?.total_points ?? '—'} />
        <StatTile label="Current streak" value={gamification ? `${gamification.current_streak_days} days` : '—'} />
        <StatTile label="Badges earned" value={gamification?.badges.length ?? '—'} />
      </div>

      <div className="rounded-xl border border-slate-200 bg-white p-5">
        <div className="mb-4 flex items-center justify-between">
          <h2 className="text-lg font-semibold text-slate-900">GCE Simulation</h2>
          <button
            onClick={startGceSimulation}
            disabled={startingGce}
            className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-50"
          >
            {startingGce ? 'Starting...' : 'Start full 50-question simulation'}
          </button>
        </div>
        <p className="text-sm text-slate-500">50 questions across every topic, timed, with a predicted GCE grade at the end.</p>
      </div>

      <div className="rounded-xl border border-slate-200 bg-white p-5">
        <h2 className="mb-4 text-lg font-semibold text-slate-900">Topic weakness diagnosis</h2>
        <div className="flex flex-col gap-3">
          {topics.map((topic) => {
            const m = masteryByTopic.get(topic.id)
            const score = m?.mastery_score ?? null
            const color = score !== null ? MASTERY_COLOR(score) : { bar: '#c3c2b7', label: 'Not started' }

            return (
              <div key={topic.id} className="flex items-center gap-4">
                <div className="w-56 shrink-0 text-sm font-medium text-slate-700">{topic.name}</div>
                <div className="h-3 flex-1 overflow-hidden rounded-full bg-slate-100">
                  <div
                    className="h-full rounded-full transition-all"
                    style={{ width: `${score ?? 0}%`, backgroundColor: color.bar }}
                  />
                </div>
                <span className="w-24 shrink-0 text-right text-xs font-medium text-slate-500">
                  {score !== null ? `${score.toFixed(0)}% · ${color.label}` : color.label}
                </span>
                <button
                  onClick={() => startTopicPractice(topic.id)}
                  disabled={startingTopicId === topic.id}
                  className="shrink-0 rounded-md border border-emerald-600 px-3 py-1.5 text-xs font-semibold text-emerald-700 hover:bg-emerald-50 disabled:opacity-50"
                >
                  {startingTopicId === topic.id ? 'Starting...' : 'Practice'}
                </button>
              </div>
            )
          })}
        </div>
      </div>
    </div>
  )
}

function StatTile({ label, value }: { label: string; value: string | number }) {
  return (
    <div className="rounded-xl border border-slate-200 bg-white p-5">
      <p className="text-sm text-slate-500">{label}</p>
      <p className="mt-1 text-2xl font-bold text-slate-900">{value}</p>
    </div>
  )
}
