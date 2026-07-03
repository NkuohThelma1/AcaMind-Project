import type { ReactNode } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { useAuth } from '../context/AuthContext'
import { useQuizSession } from '../context/QuizSessionContext'

function NavLink({ to, children }: { to: string; children: ReactNode }) {
  return (
    <Link to={to} className="rounded-md px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100 hover:text-slate-900">
      {children}
    </Link>
  )
}

export function Layout({ children }: { children: ReactNode }) {
  const { user, logout } = useAuth()
  const navigate = useNavigate()
  const { isActive, requestExit } = useQuizSession()

  async function handleLogout() {
    await logout()
    navigate('/login')
  }

  if (isActive) {
    return (
      <div className="min-h-screen bg-slate-50">
        <header className="border-b border-amber-300 bg-amber-50">
          <div className="mx-auto flex max-w-6xl items-center justify-between px-4 py-3">
            <span className="text-lg font-bold text-emerald-700">AcaMind</span>
            <div className="flex items-center gap-3">
              <span className="text-sm font-medium text-amber-800">Session in progress - navigation is locked</span>
              <button
                onClick={requestExit}
                className="rounded-md border border-red-300 px-3 py-1.5 text-sm font-semibold text-red-700 hover:bg-red-50"
              >
                End session
              </button>
            </div>
          </div>
        </header>

        <main className="mx-auto max-w-6xl px-4 py-6">{children}</main>
      </div>
    )
  }

  return (
    <div className="min-h-screen bg-slate-50">
      <header className="border-b border-slate-200 bg-white">
        <div className="mx-auto flex max-w-6xl items-center justify-between px-4 py-3">
          <Link to="/dashboard" className="text-lg font-bold text-emerald-700">
            AcaMind
          </Link>

          {user && (
            <nav className="flex items-center gap-1">
              {user.role === 'student' && (
                <>
                  <NavLink to="/dashboard">Dashboard</NavLink>
                  <NavLink to="/study-plan">Study Plan</NavLink>
                  <NavLink to="/predictions">Predictions</NavLink>
                  <NavLink to="/ai-coach">AI Coach</NavLink>
                  <NavLink to="/my-classes">My Classes</NavLink>
                  <NavLink to="/peer-groups">Peer Groups</NavLink>
                  <NavLink to="/parent-contacts">Parent Contacts</NavLink>
                  <NavLink to="/subscription">Subscription</NavLink>
                </>
              )}
              {user.role === 'teacher_verified' && (
                <>
                  <NavLink to="/dashboard">Classes</NavLink>
                  <NavLink to="/resources">Resources</NavLink>
                </>
              )}
              {user.role === 'teacher_pending' && <NavLink to="/dashboard">Pending Approval</NavLink>}
              {user.role === 'admin' && <NavLink to="/dashboard">Teacher Verification</NavLink>}

              <div className="ml-3 flex items-center gap-2 border-l border-slate-200 pl-3">
                <span className="text-sm text-slate-500">{user.name}</span>
                <button
                  onClick={handleLogout}
                  className="rounded-md px-3 py-1.5 text-sm font-medium text-slate-500 hover:bg-slate-100"
                >
                  Log out
                </button>
              </div>
            </nav>
          )}
        </div>
      </header>

      <main className="mx-auto max-w-6xl px-4 py-6">{children}</main>
    </div>
  )
}
