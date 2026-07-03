import { Link, useLocation, useNavigate } from 'react-router-dom'
import type { QuizAttempt } from '../types'

export function QuizResultPage() {
  const location = useLocation()
  const navigate = useNavigate()
  const attempt = (location.state as { attempt: QuizAttempt } | null)?.attempt

  if (!attempt) {
    navigate('/dashboard')
    return null
  }

  const scorePercent = Number(attempt.score_percent ?? 0)

  return (
    <div className="mx-auto max-w-lg text-center">
      <div className="rounded-xl border border-slate-200 bg-white p-8">
        <p className="text-sm font-medium text-slate-500">
          {attempt.type === 'gce_simulation' ? 'GCE Simulation complete' : 'Topic practice complete'}
        </p>
        <p className="mt-2 text-5xl font-bold text-emerald-600">{scorePercent.toFixed(0)}%</p>
        <p className="mt-1 text-sm text-slate-500">
          {attempt.correct_count} / {attempt.total_questions} correct
        </p>

        {attempt.predicted_grade && (
          <div className="mt-6 rounded-lg bg-indigo-50 px-4 py-3">
            <p className="text-sm text-indigo-600">Predicted GCE grade</p>
            <p className="text-3xl font-bold text-indigo-700">{attempt.predicted_grade}</p>
          </div>
        )}

        <div className="mt-6 flex justify-center gap-3">
          <Link to="/dashboard" className="rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">
            Back to Dashboard
          </Link>
          <Link to="/study-plan" className="rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
            View Study Plan
          </Link>
        </div>
      </div>
    </div>
  )
}
