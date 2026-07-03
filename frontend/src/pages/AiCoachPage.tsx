import { useEffect, useRef, useState, type FormEvent } from 'react'
import { apiClient, apiErrorMessage } from '../api/client'
import type { AiConversation, AiMessage } from '../types'

export function AiCoachPage() {
  const [conversations, setConversations] = useState<AiConversation[]>([])
  const [activeId, setActiveId] = useState<number | null>(null)
  const [messages, setMessages] = useState<AiMessage[]>([])
  const [draft, setDraft] = useState('')
  const [isSending, setIsSending] = useState(false)
  const [error, setError] = useState<string | null>(null)
  const messagesEndRef = useRef<HTMLDivElement>(null)

  function loadConversations() {
    apiClient.get<AiConversation[]>('/ai-conversations').then(({ data }) => setConversations(data))
  }

  useEffect(() => {
    loadConversations()
  }, [])

  useEffect(() => {
    if (!activeId) {
      setMessages([])
      return
    }
    apiClient.get<AiConversation>(`/ai-conversations/${activeId}`).then(({ data }) => setMessages(data.messages ?? []))
  }, [activeId])

  useEffect(() => {
    messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' })
  }, [messages])

  async function startNewConversation() {
    setError(null)
    const { data } = await apiClient.post<AiConversation>('/ai-conversations')
    setConversations((prev) => [data, ...prev])
    setActiveId(data.id)
  }

  async function handleSend(e: FormEvent) {
    e.preventDefault()
    if (!draft.trim()) return
    setError(null)

    let conversationId = activeId
    if (!conversationId) {
      const { data } = await apiClient.post<AiConversation>('/ai-conversations')
      setConversations((prev) => [data, ...prev])
      conversationId = data.id
      setActiveId(conversationId)
    }

    const userMessage: AiMessage = {
      id: Date.now(),
      ai_conversation_id: conversationId,
      role: 'user',
      content: draft,
      created_at: new Date().toISOString(),
    }
    setMessages((prev) => [...prev, userMessage])
    const sentDraft = draft
    setDraft('')
    setIsSending(true)

    try {
      const { data } = await apiClient.post<AiMessage>(`/ai-conversations/${conversationId}/messages`, { message: sentDraft })
      setMessages((prev) => [...prev, data])
      loadConversations()
    } catch (err) {
      setError(apiErrorMessage(err))
    } finally {
      setIsSending(false)
    }
  }

  return (
    <div className="mx-auto flex h-[calc(100vh-140px)] max-w-4xl gap-4">
      <aside className="flex w-56 shrink-0 flex-col gap-2 overflow-y-auto rounded-xl border border-slate-200 bg-white p-3">
        <button
          onClick={startNewConversation}
          className="mb-2 rounded-md bg-emerald-600 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-700"
        >
          + New chat
        </button>
        {conversations.map((c) => (
          <button
            key={c.id}
            onClick={() => setActiveId(c.id)}
            className={`truncate rounded-md px-3 py-2 text-left text-sm ${
              activeId === c.id ? 'bg-emerald-50 font-medium text-emerald-800' : 'text-slate-600 hover:bg-slate-50'
            }`}
          >
            {c.title || 'New conversation'}
          </button>
        ))}
        {conversations.length === 0 && <p className="px-3 text-xs text-slate-400">No conversations yet.</p>}
      </aside>

      <div className="flex flex-1 flex-col rounded-xl border border-slate-200 bg-white">
        <div className="border-b border-slate-200 px-4 py-3">
          <h1 className="text-lg font-bold text-slate-900">AI Academic Coach</h1>
          <p className="text-xs text-slate-500">Ask about any Biology GCE topic - answers are tailored to your weak areas.</p>
        </div>

        <div className="flex-1 overflow-y-auto px-4 py-4">
          {messages.length === 0 && (
            <p className="text-sm text-slate-400">Ask a question to get started, e.g. "Explain osmosis with an example."</p>
          )}
          <div className="flex flex-col gap-3">
            {messages.map((m) => (
              <div key={m.id} className={`flex ${m.role === 'user' ? 'justify-end' : 'justify-start'}`}>
                <div
                  className={`max-w-[80%] whitespace-pre-wrap rounded-lg px-3 py-2 text-sm ${
                    m.role === 'user' ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-800'
                  }`}
                >
                  {m.content}
                </div>
              </div>
            ))}
            {isSending && <div className="text-xs text-slate-400">AI coach is thinking...</div>}
            <div ref={messagesEndRef} />
          </div>
        </div>

        {error && <p className="px-4 pb-2 text-sm text-red-600">{error}</p>}

        <form onSubmit={handleSend} className="flex items-center gap-2 border-t border-slate-200 p-3">
          <input
            value={draft}
            onChange={(e) => setDraft(e.target.value)}
            placeholder="Ask your AI coach anything about Biology..."
            className="flex-1 rounded-md border border-slate-300 px-3 py-2 text-sm"
          />
          <button
            type="submit"
            disabled={isSending || !draft.trim()}
            className="rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700 disabled:opacity-50"
          >
            Send
          </button>
        </form>
      </div>
    </div>
  )
}
