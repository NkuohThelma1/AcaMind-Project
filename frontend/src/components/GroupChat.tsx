import { useEffect, useRef, useState, type FormEvent } from 'react'
import { apiClient, apiErrorMessage } from '../api/client'
import { useAuth } from '../context/AuthContext'
import type { GroupMessage } from '../types'

const POLL_INTERVAL_MS = 4000

export function GroupChat({ kind, id }: { kind: 'peer-groups' | 'classes'; id: string | number }) {
  const { user } = useAuth()
  const [messages, setMessages] = useState<GroupMessage[]>([])
  const [draft, setDraft] = useState('')
  const [error, setError] = useState<string | null>(null)
  const [isSending, setIsSending] = useState(false)
  const messagesEndRef = useRef<HTMLDivElement>(null)

  function loadMessages() {
    apiClient.get<GroupMessage[]>(`/${kind}/${id}/messages`).then(({ data }) => setMessages(data))
  }

  useEffect(() => {
    loadMessages()
    const interval = setInterval(loadMessages, POLL_INTERVAL_MS)
    return () => clearInterval(interval)
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [kind, id])

  useEffect(() => {
    messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' })
  }, [messages])

  async function handleSend(e: FormEvent) {
    e.preventDefault()
    if (!draft.trim()) return
    setError(null)
    setIsSending(true)
    try {
      await apiClient.post(`/${kind}/${id}/messages`, { content: draft })
      setDraft('')
      loadMessages()
    } catch (err) {
      setError(apiErrorMessage(err))
    } finally {
      setIsSending(false)
    }
  }

  return (
    <div className="flex h-96 flex-col rounded-xl border border-slate-200 bg-white">
      <div className="border-b border-slate-200 px-4 py-2">
        <h2 className="text-sm font-semibold text-slate-900">Group Chat</h2>
      </div>

      <div className="flex-1 overflow-y-auto px-4 py-3">
        {messages.length === 0 && <p className="text-sm text-slate-400">No messages yet - say hello!</p>}
        <div className="flex flex-col gap-2">
          {messages.map((m) => {
            const isMine = m.user.id === user?.id
            return (
              <div key={m.id} className={`flex ${isMine ? 'justify-end' : 'justify-start'}`}>
                <div className={`max-w-[75%] rounded-lg px-3 py-1.5 text-sm ${isMine ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-800'}`}>
                  {!isMine && <p className="mb-0.5 text-xs font-semibold text-slate-500">{m.user.name}</p>}
                  <p className="whitespace-pre-wrap">{m.content}</p>
                </div>
              </div>
            )
          })}
          <div ref={messagesEndRef} />
        </div>
      </div>

      {error && <p className="px-4 pb-1 text-xs text-red-600">{error}</p>}

      <form onSubmit={handleSend} className="flex items-center gap-2 border-t border-slate-200 p-2">
        <input
          value={draft}
          onChange={(e) => setDraft(e.target.value)}
          placeholder="Share an idea..."
          className="flex-1 rounded-md border border-slate-300 px-3 py-1.5 text-sm"
        />
        <button
          type="submit"
          disabled={isSending || !draft.trim()}
          className="rounded-md bg-emerald-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-emerald-700 disabled:opacity-50"
        >
          Send
        </button>
      </form>
    </div>
  )
}
