import { useEffect, useState, type FormEvent } from 'react'
import { Link } from 'react-router-dom'
import { apiClient, apiErrorMessage } from '../api/client'
import type { Level, PeerGroup } from '../types'

export function PeerGroupsPage() {
  const [groups, setGroups] = useState<PeerGroup[]>([])
  const [levels, setLevels] = useState<Level[]>([])
  const [name, setName] = useState('')
  const [levelId, setLevelId] = useState<number | null>(null)
  const [joinCode, setJoinCode] = useState('')
  const [error, setError] = useState<string | null>(null)

  function loadGroups() {
    apiClient.get<PeerGroup[]>('/peer-groups').then(({ data }) => setGroups(data))
  }

  useEffect(() => {
    loadGroups()
    apiClient.get<Level[]>('/levels').then(({ data }) => {
      setLevels(data)
      if (data.length > 0) setLevelId(data[0].id)
    })
  }, [])

  async function handleCreate(e: FormEvent) {
    e.preventDefault()
    if (!name || !levelId) return
    setError(null)
    try {
      await apiClient.post('/peer-groups', { name, level_id: levelId })
      setName('')
      loadGroups()
    } catch (err) {
      setError(apiErrorMessage(err))
    }
  }

  async function handleJoin(e: FormEvent) {
    e.preventDefault()
    if (!joinCode) return
    setError(null)
    try {
      await apiClient.post('/peer-groups/join', { join_code: joinCode })
      setJoinCode('')
      loadGroups()
    } catch (err) {
      setError(apiErrorMessage(err))
    }
  }

  return (
    <div className="mx-auto max-w-2xl">
      <h1 className="mb-6 text-2xl font-bold text-slate-900">Peer Groups</h1>

      <div className="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
        <form onSubmit={handleCreate} className="flex flex-col gap-2 rounded-xl border border-slate-200 bg-white p-4">
          <p className="text-sm font-semibold text-slate-700">Create a group</p>
          <input
            value={name}
            onChange={(e) => setName(e.target.value)}
            placeholder="Group name"
            required
            className="rounded-md border border-slate-300 px-3 py-2 text-sm"
          />
          <select value={levelId ?? ''} onChange={(e) => setLevelId(Number(e.target.value))} className="rounded-md border border-slate-300 px-3 py-2 text-sm">
            {levels.map((l) => (
              <option key={l.id} value={l.id}>
                {l.name}
              </option>
            ))}
          </select>
          <button type="submit" className="rounded-md bg-emerald-600 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-700">
            Create
          </button>
        </form>

        <form onSubmit={handleJoin} className="flex flex-col gap-2 rounded-xl border border-slate-200 bg-white p-4">
          <p className="text-sm font-semibold text-slate-700">Join with a code</p>
          <input
            value={joinCode}
            onChange={(e) => setJoinCode(e.target.value.toUpperCase())}
            placeholder="JOIN CODE"
            required
            className="rounded-md border border-slate-300 px-3 py-2 text-sm uppercase"
          />
          <button type="submit" className="mt-auto rounded-md bg-slate-800 px-3 py-2 text-sm font-semibold text-white hover:bg-slate-900">
            Join group
          </button>
        </form>
      </div>

      {error && <p className="mb-4 text-sm text-red-600">{error}</p>}

      <ul className="flex flex-col gap-3">
        {groups.map((g) => (
          <li key={g.id}>
            <Link
              to={`/peer-groups/${g.id}`}
              className="flex items-center justify-between rounded-lg border border-slate-200 bg-white p-4 hover:border-emerald-400"
            >
              <span className="font-medium text-slate-900">{g.name}</span>
              <span className="text-xs text-slate-500">Code: {g.join_code}</span>
            </Link>
          </li>
        ))}
      </ul>
    </div>
  )
}
