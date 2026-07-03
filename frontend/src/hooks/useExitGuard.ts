import { useEffect } from 'react'

const LEAVE_WARNING = 'Leaving now will lose your progress on this question. Are you sure you want to leave?'

/**
 * Blocks closing the tab, refreshing, or typing a new URL while active, via
 * the browser's native beforeunload prompt. In-app navigation (back/forward,
 * clicking a link) is handled separately by useQuizLockdown via React
 * Router's useBlocker, since beforeunload does not fire for client-side
 * route changes and a raw popstate listener can't reliably beat the
 * router's own internal listener.
 */
export function useExitGuard(isActive: boolean) {
  useEffect(() => {
    if (!isActive) return

    function handleBeforeUnload(e: BeforeUnloadEvent) {
      e.preventDefault()
      e.returnValue = LEAVE_WARNING
      return LEAVE_WARNING
    }

    window.addEventListener('beforeunload', handleBeforeUnload)
    return () => window.removeEventListener('beforeunload', handleBeforeUnload)
  }, [isActive])
}
