import { useEffect, useState, type FormEvent } from 'react'
import { Link } from 'react-router-dom'
import { apiClient, apiErrorMessage } from '../api/client'
import type { SchoolClass } from '../types'

export function StudentClassesPage() {
  const [classes, setClasses] = useState<SchoolClass[]>([])
  const [joinCode, setJoinCode] = useState('')
  const [error, setError] = useState<string | null>(null)

  function loadClasses() {
    apiClient.get<SchoolClass[]>('/classes/mine').then(({ data }) => setClasses(data))
  }

  useEffect(loadClasses, [])

  async function handleJoin(e: FormEvent) {
    e.preventDefault()
    if (!joinCode) return
    setError(null)
    try {
      await apiClient.post('/classes/join', { join_code: joinCode })
      setJoinCode('')
      loadClasses()
    } catch (err) {
      setError(apiErrorMessage(err))
    }
  }

  return (
    <div className="mx-auto max-w-2xl">
      <h1 className="mb-6 text-2xl font-bold text-slate-900">My Classes</h1>

      <form onSubmit={handleJoin} className="mb-6 flex items-end gap-3 rounded-xl border border-slate-200 bg-white p-4">
        <div className="flex-1">
          <label htmlFor="join_code" className="mb-1 block text-xs font-medium text-slate-600">
            Join a class with a code
          </label>
          <input
            id="join_code"
            value={joinCode}
            onChange={(e) => setJoinCode(e.target.value.toUpperCase())}
            placeholder="JOIN CODE"
            required
            className="w-full rounded-md border border-slate-300 px-3 py-2 text-sm uppercase"
          />
        </div>
        <button type="submit" className="rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">
          Join class
        </button>
      </form>

      {error && <p className="mb-4 text-sm text-red-600">{error}</p>}

      <ul className="flex flex-col gap-3">
        {classes.map((c) => (
          <li key={c.id}>
            <Link
              to={`/classes/${c.id}`}
              className="flex items-center justify-between rounded-lg border border-slate-200 bg-white p-4 hover:border-emerald-400"
            >
              <div>
                <p className="font-medium text-slate-900">{c.name}</p>
                <p className="text-xs text-slate-500">
                  {c.level?.name} · taught by {c.teacher?.name}
                </p>
              </div>
              <span className="text-sm text-emerald-700">Open →</span>
            </Link>
          </li>
        ))}
        {classes.length === 0 && <p className="text-sm text-slate-500">You haven't joined a class yet.</p>}
      </ul>
    </div>
  )
}
