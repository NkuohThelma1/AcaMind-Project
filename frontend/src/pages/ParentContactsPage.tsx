import { useEffect, useState, type FormEvent } from 'react'
import { apiClient, apiErrorMessage } from '../api/client'
import type { ParentContact } from '../types'

export function ParentContactsPage() {
  const [contacts, setContacts] = useState<ParentContact[]>([])
  const [name, setName] = useState('')
  const [email, setEmail] = useState('')
  const [phoneNumber, setPhoneNumber] = useState('')
  const [relationship, setRelationship] = useState('')
  const [error, setError] = useState<string | null>(null)
  const [notice, setNotice] = useState<string | null>(null)
  const [sendingId, setSendingId] = useState<number | null>(null)

  function loadContacts() {
    apiClient.get<ParentContact[]>('/parent-contacts').then(({ data }) => setContacts(data))
  }

  useEffect(() => {
    loadContacts()
  }, [])

  async function handleCreate(e: FormEvent) {
    e.preventDefault()
    if (!name || !email) return
    setError(null)
    setNotice(null)
    try {
      await apiClient.post('/parent-contacts', {
        name,
        email,
        phone_number: phoneNumber || undefined,
        relationship: relationship || undefined,
      })
      setName('')
      setEmail('')
      setPhoneNumber('')
      setRelationship('')
      loadContacts()
    } catch (err) {
      setError(apiErrorMessage(err))
    }
  }

  async function handleRemove(id: number) {
    setError(null)
    setNotice(null)
    try {
      await apiClient.delete(`/parent-contacts/${id}`)
      loadContacts()
    } catch (err) {
      setError(apiErrorMessage(err))
    }
  }

  async function handleSendNow(id: number) {
    setError(null)
    setNotice(null)
    setSendingId(id)
    try {
      const { data } = await apiClient.post<{ message: string; sent: boolean }>(`/parent-contacts/${id}/send-report`)
      if (data.sent) {
        setNotice(data.message)
      } else {
        setError(data.message)
      }
    } catch (err) {
      setError(apiErrorMessage(err))
    } finally {
      setSendingId(null)
    }
  }

  const activeContacts = contacts.filter((c) => c.is_active)

  return (
    <div className="mx-auto max-w-2xl">
      <h1 className="mb-1 text-2xl font-bold text-slate-900">Parent Contacts</h1>
      <p className="mb-6 text-sm text-slate-500">
        Add a parent or guardian's email to receive a weekly progress report - quizzes completed, topic mastery, study
        plan progress, and predicted GCE grade.
      </p>

      <form onSubmit={handleCreate} className="mb-6 flex flex-col gap-2 rounded-xl border border-slate-200 bg-white p-4">
        <p className="text-sm font-semibold text-slate-700">Add a parent contact</p>
        <div className="grid grid-cols-1 gap-2 sm:grid-cols-2">
          <input
            value={name}
            onChange={(e) => setName(e.target.value)}
            placeholder="Full name"
            required
            className="rounded-md border border-slate-300 px-3 py-2 text-sm"
          />
          <input
            value={email}
            onChange={(e) => setEmail(e.target.value)}
            type="email"
            placeholder="Email address"
            required
            className="rounded-md border border-slate-300 px-3 py-2 text-sm"
          />
          <input
            value={phoneNumber}
            onChange={(e) => setPhoneNumber(e.target.value)}
            placeholder="Phone number (optional)"
            className="rounded-md border border-slate-300 px-3 py-2 text-sm"
          />
          <input
            value={relationship}
            onChange={(e) => setRelationship(e.target.value)}
            placeholder="Relationship (e.g. Mother)"
            className="rounded-md border border-slate-300 px-3 py-2 text-sm"
          />
        </div>
        <button type="submit" className="mt-2 rounded-md bg-emerald-600 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-700">
          Add contact
        </button>
      </form>

      {error && <p className="mb-4 text-sm text-red-600">{error}</p>}
      {notice && <p className="mb-4 text-sm text-emerald-700">{notice}</p>}

      <ul className="flex flex-col gap-3">
        {activeContacts.map((c) => (
          <li key={c.id} className="flex items-center justify-between rounded-lg border border-slate-200 bg-white p-4">
            <div>
              <p className="font-medium text-slate-900">{c.name}</p>
              <p className="text-xs text-slate-500">
                {c.email}
                {c.relationship ? ` - ${c.relationship}` : ''}
              </p>
            </div>
            <div className="flex items-center gap-2">
              <button
                onClick={() => handleSendNow(c.id)}
                disabled={sendingId === c.id}
                className="rounded-md border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-100 disabled:opacity-50"
              >
                {sendingId === c.id ? 'Sending...' : 'Send report now'}
              </button>
              <button
                onClick={() => handleRemove(c.id)}
                className="rounded-md border border-red-300 px-3 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-50"
              >
                Remove
              </button>
            </div>
          </li>
        ))}
        {activeContacts.length === 0 && <p className="text-sm text-slate-500">No parent contacts added yet.</p>}
      </ul>
    </div>
  )
}
