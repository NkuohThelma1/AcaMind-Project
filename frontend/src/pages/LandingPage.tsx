import { Link, Navigate } from 'react-router-dom'
import { useAuth } from '../context/AuthContext'

const FACTORS = [
  {
    factor: 'Prior knowledge',
    feature: 'AI-powered adaptive diagnosis',
    description: 'Topic-based quizzes that adjust difficulty in real time and pinpoint exactly which topics are weak.',
  },
  {
    factor: 'Study habits',
    feature: 'Personalized study plans',
    description: 'Auto-generated, spaced-out plans ranked by weakness and exam weight — never crammed.',
  },
  {
    factor: 'Motivation & anxiety',
    feature: 'GCE grade prediction',
    description: 'Full 50-question timed simulations map straight onto a predicted grade, so progress is visible.',
  },
  {
    factor: 'Home environment',
    feature: 'Parent SMS reports',
    description: 'Weekly progress summaries sent straight to a parent’s phone — no app required on their end.',
  },
  {
    factor: 'Teacher effectiveness',
    feature: 'Teacher analytics dashboard',
    description: 'Class-wide mastery heatmaps and at-risk student alerts for every class a teacher runs.',
  },
  {
    factor: 'Motivation (peer/gamified)',
    feature: 'Points, streaks, badges & peer challenges',
    description: 'Points, streaks, and leaderboard challenges with study groups keep momentum going.',
  },
]

export function LandingPage() {
  const { user, isLoading } = useAuth()

  if (!isLoading && user) {
    return <Navigate to="/dashboard" replace />
  }

  return (
    <div className="min-h-screen bg-white">
      <header className="border-b border-slate-200">
        <div className="mx-auto flex max-w-6xl items-center justify-between px-4 py-4">
          <span className="text-lg font-bold text-emerald-700">AcaMind</span>
          <div className="flex items-center gap-3">
            <Link to="/login" className="rounded-md px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100">
              Log in
            </Link>
            <Link to="/register" className="rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">
              Get started
            </Link>
          </div>
        </div>
      </header>

      <section className="mx-auto max-w-4xl px-4 py-20 text-center">
        <h1 className="text-4xl font-bold text-slate-900 sm:text-5xl">
          Close the 80% gap that decides your GCE Biology grade.
        </h1>
        <p className="mx-auto mt-5 max-w-2xl text-lg text-slate-600">
          Yuomeyse (2026) found that student-related factors — motivation, study habits, and prior knowledge —
          explain 80% of test-score variance for Cameroonian secondary students. AcaMind is built to fix exactly
          those factors, not just deliver more content.
        </p>
        <div className="mt-8 flex justify-center gap-3">
          <Link to="/register" className="rounded-md bg-emerald-600 px-6 py-3 text-sm font-semibold text-white hover:bg-emerald-700">
            Start diagnosing your weak topics
          </Link>
          <Link to="/login" className="rounded-md border border-slate-300 px-6 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50">
            Log in
          </Link>
        </div>
      </section>

      <section className="border-t border-slate-200 bg-slate-50 py-16">
        <div className="mx-auto max-w-6xl px-4">
          <h2 className="mb-2 text-center text-2xl font-bold text-slate-900">Every feature maps to a real factor</h2>
          <p className="mb-10 text-center text-sm text-slate-500">
            Not another generic quiz app — each tool below targets one specific driver of the score gap.
          </p>
          <div className="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
            {FACTORS.map((f) => (
              <div key={f.feature} className="rounded-xl border border-slate-200 bg-white p-5">
                <p className="mb-1 text-xs font-semibold uppercase tracking-wide text-emerald-600">{f.factor}</p>
                <h3 className="mb-2 text-base font-semibold text-slate-900">{f.feature}</h3>
                <p className="text-sm text-slate-600">{f.description}</p>
              </div>
            ))}
          </div>
        </div>
      </section>

      <section className="py-16">
        <div className="mx-auto max-w-4xl px-4 text-center">
          <h2 className="mb-3 text-2xl font-bold text-slate-900">Biology O-Level & A-Level, free to start</h2>
          <p className="mb-8 text-sm text-slate-600">
            Free plan included — daily topic quizzes, monthly GCE simulations, and a personalized study plan.
            Upgrade any time for unlimited practice, parent SMS reports, and paper marking.
          </p>
          <Link to="/register" className="rounded-md bg-emerald-600 px-6 py-3 text-sm font-semibold text-white hover:bg-emerald-700">
            Create your free account
          </Link>
        </div>
      </section>

      <footer className="border-t border-slate-200 py-8 text-center text-sm text-slate-400">
        AcaMind — built for Cameroonian GCE Biology students, parents, and teachers.
      </footer>
    </div>
  )
}
