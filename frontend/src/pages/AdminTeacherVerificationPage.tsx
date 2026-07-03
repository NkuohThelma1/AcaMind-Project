import { useEffect, useState } from 'react'
import { apiClient, apiErrorMessage } from '../api/client'
import type { DeactivatedTeacher, LearningResource, TeacherApplication, VerificationDocumentType } from '../types'

const DOCUMENT_LABELS: { type: VerificationDocumentType; label: string }[] = [
  { type: 'national_id', label: 'National ID' },
  { type: 'degree_certificate', label: 'Degree Certificate' },
  { type: 'teaching_qualification', label: 'Teaching Qualification' },
  { type: 'cv', label: 'CV/Resume' },
]

async function downloadDocument(teacherId: number, type: VerificationDocumentType) {
  // The backend serves these with Content-Disposition: attachment (private
  // disk, so no inline URL exists), so a downloaded-then-opened-locally file
  // is the actual behavior - a synthetic anchor click triggers that directly,
  // without the popup-blocker issues of window.open() after an async fetch.
  const { data } = await apiClient.get(`/admin/teachers/${teacherId}/documents/${type}`, { responseType: 'blob' })
  const url = window.URL.createObjectURL(data as Blob)
  const link = document.createElement('a')
  link.href = url
  link.download = `${type}-${teacherId}`
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
  setTimeout(() => window.URL.revokeObjectURL(url), 60_000)
}

function TeacherVerificationSection() {
  const [pending, setPending] = useState<TeacherApplication[]>([])
  const [isLoading, setIsLoading] = useState(true)
  const [meetLinkDrafts, setMeetLinkDrafts] = useState<Record<number, string>>({})
  const [interviewAtDrafts, setInterviewAtDrafts] = useState<Record<number, string>>({})
  const [reasonDrafts, setReasonDrafts] = useState<Record<number, string>>({})
  const [error, setError] = useState<string | null>(null)

  function load() {
    setIsLoading(true)
    apiClient
      .get<TeacherApplication[]>('/admin/teachers/pending')
      .then(({ data }) => setPending(data))
      .finally(() => setIsLoading(false))
  }

  useEffect(load, [])

  async function approve(userId: number) {
    setError(null)
    const meetLink = meetLinkDrafts[userId]
    const interviewAt = interviewAtDrafts[userId]
    if (!meetLink || !interviewAt) {
      setError('Please provide both a Google Meet link and an interview date/time before approving.')
      return
    }
    try {
      await apiClient.post(`/admin/teachers/${userId}/approve`, { meet_link: meetLink, interview_at: interviewAt })
      setPending((prev) => prev.filter((u) => u.id !== userId))
    } catch (err) {
      setError(apiErrorMessage(err))
    }
  }

  async function reject(userId: number) {
    setError(null)
    try {
      await apiClient.post(`/admin/teachers/${userId}/reject`, { reason: reasonDrafts[userId] })
      setPending((prev) => prev.filter((u) => u.id !== userId))
    } catch (err) {
      setError(apiErrorMessage(err))
    }
  }

  return (
    <div>
      <h2 className="mb-3 text-lg font-semibold text-slate-900">Teacher Verification Queue</h2>

      {isLoading && <p className="text-sm text-slate-500">Loading...</p>}
      {!isLoading && pending.length === 0 && <p className="text-sm text-slate-500">No pending teacher applications.</p>}
      {error && <p className="mb-3 text-sm text-red-600">{error}</p>}

      <ul className="flex flex-col gap-3">
        {pending.map((teacher) => (
          <li key={teacher.id} className="rounded-lg border border-slate-200 bg-white p-4">
            <div className="mb-3">
              <p className="font-medium text-slate-900">{teacher.name}</p>
              <p className="text-xs text-slate-500">{teacher.email}</p>
            </div>

            <div className="mb-3 flex flex-wrap gap-2">
              {DOCUMENT_LABELS.map(({ type, label }) => {
                const hasDocument = teacher[`has_${type}`]
                return (
                  <button
                    key={type}
                    disabled={!hasDocument}
                    onClick={() => downloadDocument(teacher.id, type)}
                    className={`rounded-md border px-2.5 py-1 text-xs font-medium ${
                      hasDocument
                        ? 'border-emerald-300 text-emerald-700 hover:bg-emerald-50'
                        : 'border-slate-200 text-slate-300'
                    }`}
                  >
                    {hasDocument ? `Download ${label}` : `${label} missing`}
                  </button>
                )
              })}
            </div>

            <div className="mb-2 flex flex-wrap items-center gap-2">
              <input
                value={meetLinkDrafts[teacher.id] ?? ''}
                onChange={(e) => setMeetLinkDrafts((prev) => ({ ...prev, [teacher.id]: e.target.value }))}
                placeholder="Google Meet link for interview (required to approve)"
                className="flex-1 rounded-md border border-slate-300 px-3 py-1.5 text-xs"
              />
              <input
                type="datetime-local"
                aria-label="Interview date and time"
                title="Interview date and time"
                value={interviewAtDrafts[teacher.id] ?? ''}
                onChange={(e) => setInterviewAtDrafts((prev) => ({ ...prev, [teacher.id]: e.target.value }))}
                className="rounded-md border border-slate-300 px-3 py-1.5 text-xs"
              />
              <button
                onClick={() => approve(teacher.id)}
                className="rounded-md bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-700"
              >
                Approve
              </button>
            </div>

            <div className="flex items-center gap-2">
              <input
                value={reasonDrafts[teacher.id] ?? ''}
                onChange={(e) => setReasonDrafts((prev) => ({ ...prev, [teacher.id]: e.target.value }))}
                placeholder="Rejection feedback (optional) - e.g. which document to fix"
                className="flex-1 rounded-md border border-slate-300 px-3 py-1.5 text-xs"
              />
              <button
                onClick={() => reject(teacher.id)}
                className="rounded-md border border-red-300 px-3 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-50"
              >
                Reject &amp; delete
              </button>
            </div>
          </li>
        ))}
      </ul>
    </div>
  )
}

function DeactivatedTeachersSection() {
  const [teachers, setTeachers] = useState<DeactivatedTeacher[]>([])
  const [isLoading, setIsLoading] = useState(true)

  function load() {
    setIsLoading(true)
    apiClient
      .get<DeactivatedTeacher[]>('/admin/teachers/deactivated')
      .then(({ data }) => setTeachers(data))
      .finally(() => setIsLoading(false))
  }

  useEffect(load, [])

  async function reactivate(userId: number) {
    await apiClient.post(`/admin/teachers/${userId}/reactivate`)
    setTeachers((prev) => prev.filter((t) => t.id !== userId))
  }

  return (
    <div>
      <h2 className="mb-3 text-lg font-semibold text-slate-900">Deactivated Teachers</h2>

      {isLoading && <p className="text-sm text-slate-500">Loading...</p>}
      {!isLoading && teachers.length === 0 && <p className="text-sm text-slate-500">No deactivated teacher accounts.</p>}

      <ul className="flex flex-col gap-3">
        {teachers.map((teacher) => (
          <li key={teacher.id} className="flex items-center justify-between rounded-lg border border-slate-200 bg-white p-4">
            <div>
              <p className="font-medium text-slate-900">{teacher.name}</p>
              <p className="text-xs text-slate-500">
                {teacher.email} · deactivated {new Date(teacher.deactivated_at).toLocaleDateString()}
                {teacher.last_login_at && ` · last login ${new Date(teacher.last_login_at).toLocaleDateString()}`}
              </p>
            </div>
            <button
              onClick={() => reactivate(teacher.id)}
              className="rounded-md bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-700"
            >
              Reactivate
            </button>
          </li>
        ))}
      </ul>
    </div>
  )
}

function ResourceApprovalSection() {
  const [pending, setPending] = useState<LearningResource[]>([])
  const [isLoading, setIsLoading] = useState(true)
  const [reasonDrafts, setReasonDrafts] = useState<Record<number, string>>({})

  function load() {
    setIsLoading(true)
    apiClient
      .get<LearningResource[]>('/admin/learning-resources/pending')
      .then(({ data }) => setPending(data))
      .finally(() => setIsLoading(false))
  }

  useEffect(load, [])

  async function approve(id: number) {
    await apiClient.post(`/admin/learning-resources/${id}/approve`)
    setPending((prev) => prev.filter((r) => r.id !== id))
  }

  async function reject(id: number) {
    await apiClient.post(`/admin/learning-resources/${id}/reject`, { reason: reasonDrafts[id] })
    setPending((prev) => prev.filter((r) => r.id !== id))
  }

  return (
    <div>
      <h2 className="mb-3 text-lg font-semibold text-slate-900">Resource Approval Queue</h2>

      {isLoading && <p className="text-sm text-slate-500">Loading...</p>}
      {!isLoading && pending.length === 0 && <p className="text-sm text-slate-500">No resources awaiting review.</p>}

      <ul className="flex flex-col gap-3">
        {pending.map((r) => (
          <li key={r.id} className="rounded-lg border border-slate-200 bg-white p-4">
            <div className="mb-2 flex items-start justify-between">
              <div>
                <p className="font-medium text-slate-900">{r.title}</p>
                <p className="text-xs text-slate-500">
                  {r.topic?.name} · {r.type} · by {r.creator?.name}
                </p>
                {r.description && <p className="mt-1 text-sm text-slate-600">{r.description}</p>}
                {r.resource_url && (
                  <a
                    href={r.resource_url}
                    target="_blank"
                    rel="noreferrer"
                    className="mt-1 inline-block text-sm text-emerald-700 underline"
                  >
                    View resource
                  </a>
                )}
              </div>
            </div>
            <div className="flex items-center gap-2">
              <input
                value={reasonDrafts[r.id] ?? ''}
                onChange={(e) => setReasonDrafts((prev) => ({ ...prev, [r.id]: e.target.value }))}
                placeholder="Rejection reason (optional)"
                className="flex-1 rounded-md border border-slate-300 px-3 py-1.5 text-xs"
              />
              <button
                onClick={() => approve(r.id)}
                className="rounded-md bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-700"
              >
                Approve
              </button>
              <button
                onClick={() => reject(r.id)}
                className="rounded-md border border-red-300 px-3 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-50"
              >
                Reject
              </button>
            </div>
          </li>
        ))}
      </ul>
    </div>
  )
}

export function AdminTeacherVerificationPage() {
  return (
    <div className="mx-auto max-w-2xl">
      <h1 className="mb-6 text-2xl font-bold text-slate-900">Admin</h1>
      <div className="flex flex-col gap-8">
        <TeacherVerificationSection />
        <DeactivatedTeachersSection />
        <ResourceApprovalSection />
      </div>
    </div>
  )
}
