import { useEffect, useState, type FormEvent } from 'react'
import { apiClient, apiErrorMessage } from '../api/client'
import type { Level, LearningResource, LearningResourceType, Topic } from '../types'

const TYPE_OPTIONS: { value: LearningResourceType; label: string }[] = [
  { value: 'video', label: 'Academic video' },
  { value: 'pdf', label: 'PDF' },
  { value: 'notes', label: 'Notes' },
  { value: 'exercise', label: 'Revision exercise' },
  { value: 'mock_paper', label: 'Mock paper' },
  { value: 'past_paper', label: 'Past GCE paper' },
]

const STATUS_STYLES: Record<LearningResource['status'], string> = {
  pending: 'bg-amber-100 text-amber-800',
  approved: 'bg-emerald-100 text-emerald-800',
  rejected: 'bg-red-100 text-red-800',
}

export function TeacherResourcesPage() {
  const [resources, setResources] = useState<LearningResource[]>([])
  const [levels, setLevels] = useState<Level[]>([])
  const [topics, setTopics] = useState<Topic[]>([])
  const [levelId, setLevelId] = useState<number | null>(null)
  const [topicId, setTopicId] = useState<number | null>(null)
  const [title, setTitle] = useState('')
  const [description, setDescription] = useState('')
  const [type, setType] = useState<LearningResourceType>('video')
  const [url, setUrl] = useState('')
  const [file, setFile] = useState<File | null>(null)
  const [error, setError] = useState<string | null>(null)
  const [isSubmitting, setIsSubmitting] = useState(false)

  function loadResources() {
    apiClient.get<LearningResource[]>('/learning-resources/mine').then(({ data }) => setResources(data))
  }

  useEffect(() => {
    loadResources()
    apiClient.get<Level[]>('/levels').then(({ data }) => {
      setLevels(data)
      if (data.length > 0) setLevelId(data[0].id)
    })
  }, [])

  useEffect(() => {
    if (!levelId) return
    apiClient.get<Topic[]>('/topics', { params: { level_id: levelId } }).then(({ data }) => {
      setTopics(data)
      setTopicId(data.length > 0 ? data[0].id : null)
    })
  }, [levelId])

  async function handleSubmit(e: FormEvent) {
    e.preventDefault()
    if (!topicId || !title || (!url && !file)) return
    setError(null)
    setIsSubmitting(true)
    try {
      const formData = new FormData()
      formData.append('topic_id', String(topicId))
      formData.append('title', title)
      formData.append('type', type)
      if (description) formData.append('description', description)
      if (url) formData.append('url', url)
      if (file) formData.append('file', file)

      await apiClient.post('/learning-resources', formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
      })
      setTitle('')
      setDescription('')
      setUrl('')
      setFile(null)
      loadResources()
    } catch (err) {
      setError(apiErrorMessage(err))
    } finally {
      setIsSubmitting(false)
    }
  }

  return (
    <div className="mx-auto max-w-2xl">
      <h1 className="mb-1 text-2xl font-bold text-slate-900">Learning Resources</h1>
      <p className="mb-6 text-sm text-slate-500">
        Upload videos, notes, exercises, mock papers, or past GCE papers for students. An admin reviews each upload
        before it becomes visible.
      </p>

      <form onSubmit={handleSubmit} className="mb-6 flex flex-col gap-3 rounded-xl border border-slate-200 bg-white p-4">
        <p className="text-sm font-semibold text-slate-700">Upload a resource</p>

        <input
          value={title}
          onChange={(e) => setTitle(e.target.value)}
          placeholder="Title"
          required
          className="rounded-md border border-slate-300 px-3 py-2 text-sm"
        />

        <textarea
          value={description}
          onChange={(e) => setDescription(e.target.value)}
          placeholder="Description (optional)"
          rows={2}
          className="rounded-md border border-slate-300 px-3 py-2 text-sm"
        />

        <div className="grid grid-cols-1 gap-2 sm:grid-cols-3">
          <select value={levelId ?? ''} onChange={(e) => setLevelId(Number(e.target.value))} className="rounded-md border border-slate-300 px-3 py-2 text-sm">
            {levels.map((l) => (
              <option key={l.id} value={l.id}>
                {l.name}
              </option>
            ))}
          </select>
          <select value={topicId ?? ''} onChange={(e) => setTopicId(Number(e.target.value))} className="rounded-md border border-slate-300 px-3 py-2 text-sm">
            {topics.map((t) => (
              <option key={t.id} value={t.id}>
                {t.name}
              </option>
            ))}
          </select>
          <select value={type} onChange={(e) => setType(e.target.value as LearningResourceType)} className="rounded-md border border-slate-300 px-3 py-2 text-sm">
            {TYPE_OPTIONS.map((t) => (
              <option key={t.value} value={t.value}>
                {t.label}
              </option>
            ))}
          </select>
        </div>

        <input
          value={url}
          onChange={(e) => setUrl(e.target.value)}
          placeholder="Video/resource URL (for links, e.g. YouTube)"
          className="rounded-md border border-slate-300 px-3 py-2 text-sm"
        />

        <div className="flex items-center gap-2 text-xs text-slate-500">
          <span>or</span>
        </div>

        <input
          type="file"
          onChange={(e) => setFile(e.target.files?.[0] ?? null)}
          accept=".pdf,.doc,.docx,.ppt,.pptx"
          className="rounded-md border border-slate-300 px-3 py-2 text-sm"
        />

        <button
          type="submit"
          disabled={isSubmitting}
          className="rounded-md bg-emerald-600 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-700 disabled:opacity-50"
        >
          {isSubmitting ? 'Uploading...' : 'Submit for review'}
        </button>
      </form>

      {error && <p className="mb-4 text-sm text-red-600">{error}</p>}

      <ul className="flex flex-col gap-3">
        {resources.map((r) => (
          <li key={r.id} className="rounded-lg border border-slate-200 bg-white p-4">
            <div className="flex items-start justify-between">
              <div>
                <p className="font-medium text-slate-900">{r.title}</p>
                <p className="text-xs text-slate-500">
                  {r.topic?.name} · {TYPE_OPTIONS.find((t) => t.value === r.type)?.label ?? r.type}
                </p>
              </div>
              <span className={`rounded-full px-2 py-0.5 text-xs font-semibold ${STATUS_STYLES[r.status]}`}>{r.status}</span>
            </div>
            {r.status === 'rejected' && r.rejection_reason && (
              <p className="mt-2 text-xs text-red-600">Reason: {r.rejection_reason}</p>
            )}
          </li>
        ))}
        {resources.length === 0 && <p className="text-sm text-slate-500">No resources uploaded yet.</p>}
      </ul>
    </div>
  )
}
