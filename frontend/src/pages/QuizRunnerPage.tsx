import { useEffect, useRef, useState } from 'react'
import { useLocation, useNavigate } from 'react-router-dom'
import { apiClient, apiErrorMessage } from '../api/client'
import { useQuizLockdown } from '../hooks/useQuizLockdown'
import type { AnswerFeedback, QuizAttempt, ServedQuestion } from '../types'

interface LocationState {
  attempt: QuizAttempt
  question?: ServedQuestion
  questions?: ServedQuestion[]
}

export function QuizRunnerPage() {
  const location = useLocation()
  const navigate = useNavigate()
  const state = location.state as LocationState | null

  useEffect(() => {
    if (!state) {
      // Only reachable via a hard refresh mid-quiz - this build doesn't
      // support resuming an in-progress attempt without the handed-off state.
      navigate('/dashboard')
    }
  }, [state, navigate])

  if (!state) return null

  return state.attempt.type === 'topic_practice' ? (
    <TopicPracticeRunner attempt={state.attempt} firstQuestion={state.question!} />
  ) : (
    <GceSimulationRunner attempt={state.attempt} questions={state.questions!} />
  )
}

function TopicPracticeRunner({ attempt, firstQuestion }: { attempt: QuizAttempt; firstQuestion: ServedQuestion }) {
  const navigate = useNavigate()
  const { unlock } = useQuizLockdown(attempt.id)
  const [current, setCurrent] = useState(firstQuestion)
  const [selected, setSelected] = useState<string | null>(null)
  const [feedback, setFeedback] = useState<AnswerFeedback | null>(null)
  const [answeredCount, setAnsweredCount] = useState(0)
  const [error, setError] = useState<string | null>(null)
  const [isSubmitting, setIsSubmitting] = useState(false)
  const startedAt = useRef(Date.now())

  function next() {
    setFeedback(null)
    setSelected(null)
    startedAt.current = Date.now()
  }

  // When feedback arrives with the next question payload, advance current.
  const [pendingNext, setPendingNext] = useState<ServedQuestion | null>(null)

  async function submitAndAdvance() {
    if (!selected) return
    setError(null)
    setIsSubmitting(true)
    const timeTaken = Math.round((Date.now() - startedAt.current) / 1000)
    try {
      const { data } = await apiClient.post(`/quiz-attempts/${attempt.id}/answer`, {
        quiz_question_id: current.quiz_question_id,
        selected_option: selected,
        time_taken_seconds: timeTaken,
      })
      setFeedback(data.feedback)
      setAnsweredCount((c) => c + 1)
      setPendingNext(data.next_question)

      if (data.completed) {
        setTimeout(() => {
          unlock()
          navigate(`/quiz/${attempt.id}/result`, { state: { attempt: data.attempt } })
        }, 1500)
      }
    } catch (err) {
      setError(apiErrorMessage(err))
    } finally {
      setIsSubmitting(false)
    }
  }

  function goToNext() {
    if (pendingNext) {
      setCurrent(pendingNext)
      setPendingNext(null)
    }
    next()
  }

  return (
    <div className="mx-auto max-w-2xl">
      <div className="mb-4 flex items-center justify-between text-sm text-slate-500">
        <span>Topic practice</span>
        <span>
          Question {answeredCount + 1} / {attempt.total_questions}
        </span>
      </div>

      <div className="rounded-xl border border-slate-200 bg-white p-6">
        <p className="mb-5 text-lg font-medium text-slate-900">{current.question.stem}</p>

        <div className="flex flex-col gap-2">
          {Object.entries(current.question.options).map(([key, label]) => {
            const isSelected = selected === key
            const isCorrectOption = feedback && feedback.correct_option === key
            const isWrongSelected = feedback && isSelected && !feedback.is_correct

            let classes = 'border-slate-300 hover:border-emerald-400'
            if (feedback) {
              if (isCorrectOption) classes = 'border-green-500 bg-green-50'
              else if (isWrongSelected) classes = 'border-red-500 bg-red-50'
              else classes = 'border-slate-200 opacity-60'
            } else if (isSelected) {
              classes = 'border-emerald-500 bg-emerald-50'
            }

            return (
              <button
                key={key}
                disabled={!!feedback}
                onClick={() => setSelected(key)}
                className={`rounded-md border px-4 py-2.5 text-left text-sm ${classes}`}
              >
                <span className="mr-2 font-semibold">{key}.</span>
                {label}
              </button>
            )
          })}
        </div>

        {feedback && (
          <div className={`mt-4 rounded-md px-4 py-3 text-sm ${feedback.is_correct ? 'bg-green-50 text-green-800' : 'bg-red-50 text-red-800'}`}>
            <p className="font-semibold">{feedback.is_correct ? 'Correct!' : 'Not quite.'}</p>
            {feedback.explanation && <p className="mt-1">{feedback.explanation}</p>}
          </div>
        )}

        {error && <p className="mt-3 text-sm text-red-600">{error}</p>}

        <div className="mt-5 flex justify-end">
          {!feedback ? (
            <button
              onClick={submitAndAdvance}
              disabled={!selected || isSubmitting}
              className="rounded-md bg-emerald-600 px-5 py-2 text-sm font-semibold text-white hover:bg-emerald-700 disabled:opacity-50"
            >
              {isSubmitting ? 'Submitting...' : 'Submit answer'}
            </button>
          ) : pendingNext ? (
            <button
              onClick={goToNext}
              className="rounded-md bg-slate-800 px-5 py-2 text-sm font-semibold text-white hover:bg-slate-900"
            >
              Next question
            </button>
          ) : (
            <span className="text-sm text-slate-500">Finishing up...</span>
          )}
        </div>
      </div>
    </div>
  )
}

function GceSimulationRunner({ attempt, questions }: { attempt: QuizAttempt; questions: ServedQuestion[] }) {
  const navigate = useNavigate()
  const { unlock } = useQuizLockdown(attempt.id)
  const [answers, setAnswers] = useState<Record<number, string>>({})
  const [currentIndex, setCurrentIndex] = useState(0)
  const [isSubmitting, setIsSubmitting] = useState(false)
  const [error, setError] = useState<string | null>(null)
  const [secondsLeft, setSecondsLeft] = useState(attempt.time_limit_seconds ?? 0)
  const startedAt = useRef(Date.now())

  useEffect(() => {
    const interval = setInterval(() => setSecondsLeft((s) => Math.max(0, s - 1)), 1000)
    return () => clearInterval(interval)
  }, [])

  useEffect(() => {
    if (secondsLeft === 0 && !isSubmitting) {
      handleSubmitAll()
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [secondsLeft])

  function selectAnswer(quizQuestionId: number, option: string) {
    setAnswers((prev) => ({ ...prev, [quizQuestionId]: option }))
  }

  async function handleSubmitAll() {
    setIsSubmitting(true)
    setError(null)
    const timeTaken = Math.round((Date.now() - startedAt.current) / 1000 / questions.length)
    try {
      let lastResponse = null
      for (const q of questions) {
        const selectedOption = answers[q.quiz_question_id] ?? null
        const { data } = await apiClient.post(`/quiz-attempts/${attempt.id}/answer`, {
          quiz_question_id: q.quiz_question_id,
          selected_option: selectedOption,
          time_taken_seconds: timeTaken,
        })
        lastResponse = data
      }
      unlock()
      navigate(`/quiz/${attempt.id}/result`, { state: { attempt: lastResponse.attempt } })
    } catch (err) {
      setError(apiErrorMessage(err))
      setIsSubmitting(false)
    }
  }

  const current = questions[currentIndex]
  const minutes = Math.floor(secondsLeft / 60)
  const seconds = secondsLeft % 60

  return (
    <div className="grid grid-cols-[1fr_200px] gap-6">
      <div>
        <div className="mb-4 flex items-center justify-between">
          <span className="text-sm text-slate-500">
            Question {currentIndex + 1} / {questions.length}
          </span>
          <span className={`rounded-md px-3 py-1 text-sm font-semibold ${secondsLeft < 300 ? 'bg-red-50 text-red-700' : 'bg-slate-100 text-slate-700'}`}>
            {minutes}:{seconds.toString().padStart(2, '0')}
          </span>
        </div>

        <div className="rounded-xl border border-slate-200 bg-white p-6">
          <p className="mb-5 text-lg font-medium text-slate-900">{current.question.stem}</p>

          <div className="flex flex-col gap-2">
            {Object.entries(current.question.options).map(([key, label]) => (
              <button
                key={key}
                onClick={() => selectAnswer(current.quiz_question_id, key)}
                className={`rounded-md border px-4 py-2.5 text-left text-sm ${
                  answers[current.quiz_question_id] === key ? 'border-emerald-500 bg-emerald-50' : 'border-slate-300 hover:border-emerald-400'
                }`}
              >
                <span className="mr-2 font-semibold">{key}.</span>
                {label}
              </button>
            ))}
          </div>

          {error && <p className="mt-3 text-sm text-red-600">{error}</p>}

          <div className="mt-5 flex justify-between">
            <button
              disabled={currentIndex === 0}
              onClick={() => setCurrentIndex((i) => i - 1)}
              className="rounded-md border border-slate-300 px-4 py-2 text-sm font-medium text-slate-600 disabled:opacity-40"
            >
              Previous
            </button>
            {currentIndex < questions.length - 1 ? (
              <button
                onClick={() => setCurrentIndex((i) => i + 1)}
                className="rounded-md bg-slate-800 px-4 py-2 text-sm font-medium text-white"
              >
                Next
              </button>
            ) : (
              <button
                onClick={handleSubmitAll}
                disabled={isSubmitting}
                className="rounded-md bg-emerald-600 px-5 py-2 text-sm font-semibold text-white hover:bg-emerald-700 disabled:opacity-50"
              >
                {isSubmitting ? 'Submitting...' : 'Submit simulation'}
              </button>
            )}
          </div>
        </div>
      </div>

      <div className="rounded-xl border border-slate-200 bg-white p-3">
        <p className="mb-2 text-xs font-semibold text-slate-500">Questions</p>
        <div className="grid grid-cols-5 gap-1.5">
          {questions.map((q, i) => (
            <button
              key={q.quiz_question_id}
              onClick={() => setCurrentIndex(i)}
              className={`h-8 rounded text-xs font-medium ${
                i === currentIndex
                  ? 'bg-emerald-600 text-white'
                  : answers[q.quiz_question_id]
                    ? 'bg-emerald-100 text-emerald-800'
                    : 'bg-slate-100 text-slate-500'
              }`}
            >
              {i + 1}
            </button>
          ))}
        </div>
      </div>
    </div>
  )
}
