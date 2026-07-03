import { useEffect, useState, type FormEvent } from 'react'
import { useParams } from 'react-router-dom'
import { apiClient, apiErrorMessage } from '../api/client'
import { GroupChat } from '../components/GroupChat'
import type { GroupChallenge, PeerGroup, QuizAttempt } from '../types'

interface GroupDetail extends PeerGroup {
  members: { user: { id: number; name: string } }[]
  challenges: GroupChallenge[]
}

interface LeaderboardRow {
  rank: number
  student_name: string
  score_percent: string
  completed_at: string
}

export function PeerGroupDetailPage() {
  const { groupId } = useParams()
  const [group, setGroup] = useState<GroupDetail | null>(null)
  const [attempts, setAttempts] = useState<QuizAttempt[]>([])
  const [leaderboards, setLeaderboards] = useState<Record<number, LeaderboardRow[]>>({})
  const [error, setError] = useState<string | null>(null)
  const [startsAt, setStartsAt] = useState('')
  const [endsAt, setEndsAt] = useState('')

  function loadGroup() {
    apiClient.get<GroupDetail>(`/peer-groups/${groupId}`).then(({ data }) => setGroup(data))
  }

  useEffect(() => {
    loadGroup()
    apiClient.get<{ data: QuizAttempt[] }>('/quiz-attempts').then(({ data }) =>
      setAttempts(data.data.filter((a) => a.status === 'completed')),
    )
  }, [groupId])

  async function createChallenge(e: FormEvent) {
    e.preventDefault()
    setError(null)
    try {
      await apiClient.post(`/peer-groups/${groupId}/challenges`, {
        starts_at: startsAt || new Date().toISOString(),
        ends_at: endsAt || new Date(Date.now() + 7 * 86400000).toISOString(),
      })
      loadGroup()
    } catch (err) {
      setError(apiErrorMessage(err))
    }
  }

  async function submitAttempt(challengeId: number, attemptId: number) {
    setError(null)
    try {
      await apiClient.post(`/group-challenges/${challengeId}/submit`, { quiz_attempt_id: attemptId })
      loadLeaderboard(challengeId)
    } catch (err) {
      setError(apiErrorMessage(err))
    }
  }

  async function loadLeaderboard(challengeId: number) {
    const { data } = await apiClient.get<LeaderboardRow[]>(`/group-challenges/${challengeId}/leaderboard`)
    setLeaderboards((prev) => ({ ...prev, [challengeId]: data }))
  }

  if (!group) return <p className="text-sm text-slate-500">Loading group...</p>

  return (
    <div className="mx-auto max-w-2xl">
      <h1 className="mb-1 text-2xl font-bold text-slate-900">{group.name}</h1>
      <p className="mb-6 text-sm text-slate-500">
        {group.members.length} member(s) · join code {group.join_code}
      </p>

      {error && <p className="mb-4 text-sm text-red-600">{error}</p>}

      <div className="mb-6">
        <GroupChat kind="peer-groups" id={groupId!} />
      </div>

      <form onSubmit={createChallenge} className="mb-6 flex flex-wrap items-end gap-3 rounded-xl border border-slate-200 bg-white p-4">
        <div>
          <label htmlFor="challenge_starts_at" className="mb-1 block text-xs font-medium text-slate-600">Starts</label>
          <input id="challenge_starts_at" type="datetime-local" value={startsAt} onChange={(e) => setStartsAt(e.target.value)} className="rounded-md border border-slate-300 px-3 py-2 text-sm" />
        </div>
        <div>
          <label htmlFor="challenge_ends_at" className="mb-1 block text-xs font-medium text-slate-600">Ends</label>
          <input id="challenge_ends_at" type="datetime-local" value={endsAt} onChange={(e) => setEndsAt(e.target.value)} className="rounded-md border border-slate-300 px-3 py-2 text-sm" />
        </div>
        <button type="submit" className="rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">
          New challenge
        </button>
      </form>

      <div className="flex flex-col gap-4">
        {group.challenges.map((challenge) => (
          <div key={challenge.id} className="rounded-xl border border-slate-200 bg-white p-4">
            <div className="mb-3 flex items-center justify-between">
              <p className="font-medium text-slate-900">
                Challenge #{challenge.id} · {new Date(challenge.starts_at).toLocaleDateString()} - {new Date(challenge.ends_at).toLocaleDateString()}
              </p>
              <button onClick={() => loadLeaderboard(challenge.id)} className="text-xs font-semibold text-emerald-700 hover:underline">
                Refresh leaderboard
              </button>
            </div>

            {attempts.length > 0 && (
              <div className="mb-3 flex items-center gap-2 text-sm">
                <span className="text-slate-500">Submit a completed attempt:</span>
                <select
                  onChange={(e) => e.target.value && submitAttempt(challenge.id, Number(e.target.value))}
                  defaultValue=""
                  className="rounded-md border border-slate-300 px-2 py-1 text-xs"
                >
                  <option value="" disabled>
                    Select attempt
                  </option>
                  {attempts.map((a) => (
                    <option key={a.id} value={a.id}>
                      #{a.id} · {a.type} · {a.score_percent}%
                    </option>
                  ))}
                </select>
              </div>
            )}

            {leaderboards[challenge.id] && (
              <ol className="flex flex-col gap-1 text-sm">
                {leaderboards[challenge.id].map((row) => (
                  <li key={row.rank} className="flex justify-between border-b border-slate-100 py-1">
                    <span>
                      #{row.rank} {row.student_name}
                    </span>
                    <span className="font-medium">{row.score_percent}%</span>
                  </li>
                ))}
              </ol>
            )}
          </div>
        ))}
      </div>
    </div>
  )
}
