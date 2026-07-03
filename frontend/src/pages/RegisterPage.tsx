import { useEffect, useState, type FormEvent } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { useAuth } from '../context/AuthContext'
import { apiClient, apiErrorMessage } from '../api/client'
import type { Level } from '../types'

export function RegisterPage() {
  const { register } = useAuth()
  const navigate = useNavigate()
  const [name, setName] = useState('')
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [passwordConfirmation, setPasswordConfirmation] = useState('')
  const [role, setRole] = useState<'student' | 'teacher_pending'>('student')
  const [levels, setLevels] = useState<Level[]>([])
  const [levelId, setLevelId] = useState<number | null>(null)
  const [nationalId, setNationalId] = useState<File | null>(null)
  const [degreeCertificate, setDegreeCertificate] = useState<File | null>(null)
  const [teachingQualification, setTeachingQualification] = useState<File | null>(null)
  const [cv, setCv] = useState<File | null>(null)
  const [error, setError] = useState<string | null>(null)
  const [isSubmitting, setIsSubmitting] = useState(false)

  useEffect(() => {
    apiClient.get<Level[]>('/levels').then(({ data }) => {
      setLevels(data)
      if (data.length > 0) setLevelId(data[0].id)
    })
  }, [])

  async function handleSubmit(e: FormEvent) {
    e.preventDefault()
    setError(null)

    if (role === 'teacher_pending' && (!nationalId || !degreeCertificate || !teachingQualification || !cv)) {
      setError('Please upload all four verification documents.')
      return
    }

    setIsSubmitting(true)
    try {
      await register(
        {
          name,
          email,
          password,
          password_confirmation: passwordConfirmation,
          role,
          ...(role === 'student' ? { level_id: levelId ?? undefined } : {}),
        },
        role === 'teacher_pending'
          ? {
              national_id: nationalId!,
              degree_certificate: degreeCertificate!,
              teaching_qualification: teachingQualification!,
              cv: cv!,
            }
          : undefined,
      )
      navigate('/dashboard')
    } catch (err) {
      setError(apiErrorMessage(err))
    } finally {
      setIsSubmitting(false)
    }
  }

  return (
    <div className="flex min-h-screen items-center justify-center bg-slate-50 px-4">
      <div className="w-full max-w-sm rounded-xl border border-slate-200 bg-white p-8 shadow-sm">
        <h1 className="mb-1 text-2xl font-bold text-emerald-700">AcaMind</h1>
        <p className="mb-6 text-sm text-slate-500">Create your account.</p>

        <form onSubmit={handleSubmit} className="flex flex-col gap-4">
          <div>
            <label className="mb-1 block text-sm font-medium text-slate-700">I am a...</label>
            <div className="flex gap-2">
              <button
                type="button"
                onClick={() => setRole('student')}
                className={`flex-1 rounded-md border px-3 py-2 text-sm font-medium ${role === 'student' ? 'border-emerald-500 bg-emerald-50 text-emerald-700' : 'border-slate-300 text-slate-600'}`}
              >
                Student
              </button>
              <button
                type="button"
                onClick={() => setRole('teacher_pending')}
                className={`flex-1 rounded-md border px-3 py-2 text-sm font-medium ${role === 'teacher_pending' ? 'border-emerald-500 bg-emerald-50 text-emerald-700' : 'border-slate-300 text-slate-600'}`}
              >
                Teacher
              </button>
            </div>
          </div>

          {role === 'student' && (
            <div>
              <label htmlFor="level" className="mb-1 block text-sm font-medium text-slate-700">
                Which exam level are you preparing for?
              </label>
              <select
                id="level"
                required
                value={levelId ?? ''}
                onChange={(e) => setLevelId(Number(e.target.value))}
                className="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none"
              >
                {levels.map((l) => (
                  <option key={l.id} value={l.id}>
                    {l.name}
                  </option>
                ))}
              </select>
              <p className="mt-1 text-xs text-slate-400">
                This sets which syllabus you study - it isn't something you switch day to day.
              </p>
            </div>
          )}

          {role === 'teacher_pending' && (
            <div className="flex flex-col gap-3 rounded-md border border-amber-200 bg-amber-50 p-3">
              <p className="text-xs text-amber-800">
                To verify your identity and credentials, please upload the following. An admin will review these
                before your account is approved.
              </p>

              <div>
                <label htmlFor="national_id" className="mb-1 block text-sm font-medium text-slate-700">
                  National Identity Card
                </label>
                <input
                  id="national_id"
                  type="file"
                  required
                  accept=".pdf,.jpg,.jpeg,.png"
                  onChange={(e) => setNationalId(e.target.files?.[0] ?? null)}
                  className="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm"
                />
              </div>

              <div>
                <label htmlFor="degree_certificate" className="mb-1 block text-sm font-medium text-slate-700">
                  Highest Degree Certificate
                </label>
                <input
                  id="degree_certificate"
                  type="file"
                  required
                  accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"
                  onChange={(e) => setDegreeCertificate(e.target.files?.[0] ?? null)}
                  className="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm"
                />
              </div>

              <div>
                <label htmlFor="teaching_qualification" className="mb-1 block text-sm font-medium text-slate-700">
                  Teaching Qualification Certificate
                </label>
                <input
                  id="teaching_qualification"
                  type="file"
                  required
                  accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"
                  onChange={(e) => setTeachingQualification(e.target.files?.[0] ?? null)}
                  className="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm"
                />
              </div>

              <div>
                <label htmlFor="cv" className="mb-1 block text-sm font-medium text-slate-700">
                  CV / Resume
                </label>
                <input
                  id="cv"
                  type="file"
                  required
                  accept=".pdf,.doc,.docx"
                  onChange={(e) => setCv(e.target.files?.[0] ?? null)}
                  className="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm"
                />
              </div>
            </div>
          )}

          <div>
            <label htmlFor="name" className="mb-1 block text-sm font-medium text-slate-700">Full name</label>
            <input
              id="name"
              required
              value={name}
              onChange={(e) => setName(e.target.value)}
              className="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none"
            />
          </div>
          <div>
            <label htmlFor="email" className="mb-1 block text-sm font-medium text-slate-700">Email</label>
            <input
              id="email"
              type="email"
              required
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              className="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none"
            />
          </div>
          <div>
            <label htmlFor="password" className="mb-1 block text-sm font-medium text-slate-700">Password</label>
            <input
              id="password"
              type="password"
              required
              minLength={8}
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              className="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none"
            />
          </div>
          <div>
            <label htmlFor="password_confirmation" className="mb-1 block text-sm font-medium text-slate-700">Confirm password</label>
            <input
              id="password_confirmation"
              type="password"
              required
              value={passwordConfirmation}
              onChange={(e) => setPasswordConfirmation(e.target.value)}
              className="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none"
            />
          </div>

          {error && <p className="text-sm text-red-600">{error}</p>}

          <button
            type="submit"
            disabled={isSubmitting}
            className="mt-2 rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700 disabled:opacity-50"
          >
            {isSubmitting ? 'Creating account...' : 'Create account'}
          </button>
        </form>

        <p className="mt-4 text-center text-sm text-slate-500">
          Already have an account?{' '}
          <Link to="/login" className="font-medium text-emerald-700 hover:underline">
            Log in
          </Link>
        </p>
      </div>
    </div>
  )
}
