import { useEffect, useState, type FormEvent } from 'react'
import { Link } from 'react-router-dom'
import { apiClient, apiErrorMessage } from '../api/client'
import type { Level, SchoolClass } from '../types'

export function TeacherDashboardPage() {
  const [classes, setClasses] = useState<SchoolClass[]>([])
  const [levels, setLevels] = useState<Level[]>([])
  const [name, setName] = useState('')
  const [levelId, setLevelId] = useState<number | null>(null)
  const [error, setError] = useState<string | null>(null)
  const [isCreating, setIsCreating] = useState(false)

  function loadClasses() {
    apiClient.get<SchoolClass[]>('/classes').then(({ data }) => setClasses(data))
  }

  useEffect(() => {
    loadClasses()
    apiClient.get<Level[]>('/levels').then(({ data }) => {
      setLevels(data)
      if (data.length > 0) setLevelId(data[0].id)
    })
  }, [])

  async function handleCreate(e: FormEvent) {
    e.preventDefault()
    if (!levelId || !name) return
    setError(null)
    setIsCreating(true)
    try {
      await apiClient.post('/classes', { name, level_id: levelId })
      setName('')
      loadClasses()
    } catch (err) {
      setError(apiErrorMessage(err))
    } finally {
      setIsCreating(false)
    }
  }

  return (
    <div className="mx-auto max-w-2xl">
      <h1 className="mb-6 text-2xl font-bold text-slate-900">Your Classes</h1>

      <form onSubmit={handleCreate} className="mb-6 flex flex-wrap items-end gap-3 rounded-xl border border-slate-200 bg-white p-4">
        <div className="flex-1">
          <label htmlFor="class_name" className="mb-1 block text-xs font-medium text-slate-600">Class name</label>
          <input
            id="class_name"
            value={name}
            onChange={(e) => setName(e.target.value)}
            required
            placeholder="e.g. Form 5 Biology"
            className="w-full rounded-md border border-slate-300 px-3 py-2 text-sm"
          />
        </div>
        <div>
          <label htmlFor="class_level" className="mb-1 block text-xs font-medium text-slate-600">Level</label>
          <select
            id="class_level"
            value={levelId ?? ''}
            onChange={(e) => setLevelId(Number(e.target.value))}
            className="rounded-md border border-slate-300 px-3 py-2 text-sm"
          >
            {levels.map((l) => (
              <option key={l.id} value={l.id}>
                {l.name}
              </option>
            ))}
          </select>
        </div>
        <button
          type="submit"
          disabled={isCreating}
          className="rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700 disabled:opacity-50"
        >
          Create class
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
                  {c.level?.name} · {c.class_students_count ?? 0} student(s) · join code {c.join_code}
                </p>
              </div>
              <span className="text-sm text-emerald-700">View analytics →</span>
            </Link>
          </li>
        ))}
      </ul>
    </div>
  )
}
