import { useEffect, useRef, useState } from 'react'
import { useBlocker, useNavigate } from 'react-router-dom'
import { apiClient } from '../api/client'
import { useQuizSession } from '../context/QuizSessionContext'
import { useExitGuard } from './useExitGuard'

const LEAVE_WARNING = 'Ending now will submit your progress as incomplete and this session cannot be resumed. Are you sure you want to leave?'

/**
 * Locks navigation for the duration of a quiz/GCE attempt:
 *  - hides the normal nav (via QuizSessionContext, consumed by Layout)
 *  - blocks tab close/refresh/typed-URL navigation (useExitGuard, beforeunload)
 *  - blocks in-app navigation - back/forward button, any stray link - via
 *    React Router's useBlocker, which (unlike a raw popstate listener) hooks
 *    into the router's own navigation pipeline instead of racing it
 *  - wires the visible "End session" control and the back-button prompt to
 *    the same server-side abandon call, so the attempt never sits stuck
 *    in_progress forever
 *
 * Call `unlock()` right before programmatically navigating to the result
 * page on natural completion, so that navigation isn't self-blocked.
 */
export function useQuizLockdown(attemptId: number) {
  const navigate = useNavigate()
  const { startSession, endSession } = useQuizSession()
  const [isLocked, setIsLocked] = useState(true)
  const isLockedRef = useRef(true)

  async function endAttemptAndLeave() {
    try {
      await apiClient.post(`/quiz-attempts/${attemptId}/abandon`)
    } finally {
      isLockedRef.current = false
      setIsLocked(false)
      endSession()
    }
  }

  useEffect(() => {
    startSession(async () => {
      await endAttemptAndLeave()
      navigate('/dashboard')
    })

    return () => endSession()
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [attemptId])

  useExitGuard(isLocked)

  const blocker = useBlocker(() => isLockedRef.current)

  useEffect(() => {
    if (blocker.state !== 'blocked') return

    if (window.confirm(LEAVE_WARNING)) {
      endAttemptAndLeave().then(() => blocker.proceed())
    } else {
      blocker.reset()
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [blocker.state])

  return {
    unlock: () => {
      isLockedRef.current = false
      setIsLocked(false)
      endSession()
    },
  }
}
