import { useEffect, useState } from 'react'
import { CartesianGrid, Line, LineChart, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts'
import { apiClient } from '../api/client'
import { useAuth } from '../context/AuthContext'
import type { GradePrediction } from '../types'

export function PredictionsPage() {
  const { user } = useAuth()
  const [predictions, setPredictions] = useState<GradePrediction[]>([])
  const [isLoading, setIsLoading] = useState(true)

  useEffect(() => {
    if (!user?.level_id) return
    apiClient
      .get<GradePrediction[]>('/grade-predictions', { params: { level_id: user.level_id } })
      .then(({ data }) => setPredictions(data))
      .finally(() => setIsLoading(false))
  }, [user?.level_id])

  if (isLoading) return <p className="text-sm text-slate-500">Loading predictions...</p>

  if (predictions.length === 0) {
    return <p className="text-sm text-slate-500">No GCE simulations completed yet - take one from the dashboard to see your predicted grade trend.</p>
  }

  const chartData = predictions.map((p) => ({
    date: new Date(p.created_at).toLocaleDateString(undefined, { month: 'short', day: 'numeric' }),
    score: Number(p.score_percent),
    grade: p.predicted_grade,
  }))

  const latest = predictions[predictions.length - 1]

  return (
    <div className="mx-auto max-w-3xl">
      <h1 className="mb-1 text-2xl font-bold text-slate-900">Predicted GCE Grade</h1>
      <p className="mb-6 text-sm text-slate-500">Based on your GCE simulation scores over time.</p>

      <div className="mb-6 flex items-center gap-6 rounded-xl border border-slate-200 bg-white p-6">
        <div>
          <p className="text-sm text-slate-500">Latest predicted grade</p>
          <p className="text-4xl font-bold text-indigo-700">{latest.predicted_grade}</p>
        </div>
        <div className="h-12 w-px bg-slate-200" />
        <div>
          <p className="text-sm text-slate-500">Latest score</p>
          <p className="text-2xl font-semibold text-slate-900">{Number(latest.score_percent).toFixed(0)}%</p>
        </div>
      </div>

      <div className="rounded-xl border border-slate-200 bg-white p-6">
        <h2 className="mb-4 text-sm font-semibold text-slate-700">Score trend</h2>
        <ResponsiveContainer width="100%" height={280}>
          <LineChart data={chartData} margin={{ top: 8, right: 16, left: -16, bottom: 0 }}>
            <CartesianGrid strokeDasharray="3 3" stroke="#e1e0d9" vertical={false} />
            <XAxis dataKey="date" tick={{ fontSize: 12, fill: '#898781' }} axisLine={{ stroke: '#c3c2b7' }} tickLine={false} />
            <YAxis domain={[0, 100]} tick={{ fontSize: 12, fill: '#898781' }} axisLine={false} tickLine={false} />
            <Tooltip
              formatter={(value, _name, item) => [`${value}% (Grade ${item.payload.grade})`, 'Score']}
              contentStyle={{ fontSize: 13, borderRadius: 8, borderColor: '#e1e0d9' }}
            />
            <Line type="monotone" dataKey="score" stroke="#256abf" strokeWidth={2} dot={{ r: 4, fill: '#256abf' }} />
          </LineChart>
        </ResponsiveContainer>
      </div>
    </div>
  )
}
