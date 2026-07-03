import { createContext, useCallback, useContext, useMemo, useRef, useState, type ReactNode } from 'react'

interface QuizSessionContextValue {
  isActive: boolean
  startSession: (onEndSession: () => void) => void
  endSession: () => void
  requestExit: () => void
}

const QuizSessionContext = createContext<QuizSessionContextValue | undefined>(undefined)

export function QuizSessionProvider({ children }: { children: ReactNode }) {
  const [isActive, setIsActive] = useState(false)
  const endCallbackRef = useRef<() => void>(() => {})

  const startSession = useCallback((onEndSession: () => void) => {
    endCallbackRef.current = onEndSession
    setIsActive(true)
  }, [])

  const endSession = useCallback(() => {
    setIsActive(false)
  }, [])

  const requestExit = useCallback(() => {
    const confirmed = window.confirm(
      'Ending now will submit your progress as incomplete and this session cannot be resumed. Are you sure you want to end the session?',
    )
    if (confirmed) {
      endCallbackRef.current()
    }
  }, [])

  const value = useMemo(() => ({ isActive, startSession, endSession, requestExit }), [isActive, startSession, endSession, requestExit])

  return <QuizSessionContext.Provider value={value}>{children}</QuizSessionContext.Provider>
}

export function useQuizSession(): QuizSessionContextValue {
  const ctx = useContext(QuizSessionContext)
  if (!ctx) {
    throw new Error('useQuizSession must be used within a QuizSessionProvider')
  }
  return ctx
}
