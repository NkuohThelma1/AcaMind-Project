export type UserRole = 'student' | 'teacher_pending' | 'teacher_verified' | 'admin'

export interface User {
  id: number
  name: string
  email: string
  role: UserRole
  phone: string | null
  level_id: number | null
  total_points: number
  current_streak_days: number
  longest_streak_days: number
  last_activity_date: string | null
}

export type VerificationDocumentType = 'national_id' | 'degree_certificate' | 'teaching_qualification' | 'cv'

export interface TeacherApplication extends User {
  created_at: string
  has_national_id: boolean
  has_degree_certificate: boolean
  has_teaching_qualification: boolean
  has_cv: boolean
}

export interface DeactivatedTeacher extends User {
  deactivated_at: string
  last_login_at: string | null
}

export interface Level {
  id: number
  code: string
  name: string
}

export interface AiMessage {
  id: number
  ai_conversation_id: number
  role: 'user' | 'assistant'
  content: string
  created_at: string
}

export interface AiConversation {
  id: number
  user_id: number
  title: string | null
  created_at: string
  updated_at: string
  messages_count?: number
  messages?: AiMessage[]
}

export interface Topic {
  id: number
  subject_id: number
  level_id: number
  parent_topic_id: number | null
  code: string | null
  name: string
  exam_weight: number
  order: number
}

export interface Question {
  id: number
  topic_id: number
  level_id: number
  stem: string
  options: Record<string, string>
  difficulty: number
  marks: number
}

export interface ServedQuestion {
  quiz_question_id: number
  question: Question
}

export interface QuizAttempt {
  id: number
  user_id: number
  type: 'topic_practice' | 'gce_simulation'
  topic_id: number | null
  level_id: number
  status: 'in_progress' | 'completed' | 'abandoned'
  started_at: string | null
  completed_at: string | null
  total_questions: number
  correct_count: number
  score_percent: string | null
  predicted_grade: string | null
  time_limit_seconds: number | null
  duration_seconds: number | null
  topic?: Topic
  level?: Level
}

export interface AnswerFeedback {
  quiz_question_id: number
  question_id: number
  selected_option: string | null
  correct_option: string
  is_correct: boolean
  explanation: string | null
}

export interface AnswerResponse {
  attempt: QuizAttempt
  completed: boolean
  feedback: AnswerFeedback
  next_question: ServedQuestion | null
}

export interface TopicMasteryRow {
  topic_id: number
  topic_name: string
  level_id: number
  mastery_score: number
  is_weak: boolean
  trend: 'improving' | 'declining' | 'stable'
  attempts_count: number
  correct_count: number
  last_practiced_at: string | null
}

export interface StudyPlanItem {
  id: number
  study_plan_id: number
  topic_id: number
  priority_score: string
  recommended_action: 'practice_quiz' | 'review_notes' | 'retry_weak_questions'
  target_quiz_count: number
  is_completed: boolean
  completed_at: string | null
  due_at: string | null
  order: number
  topic: Topic
}

export interface StudyPlan {
  id: number
  user_id: number
  level_id: number
  status: string
  generated_at: string
  target_date: string | null
  items: StudyPlanItem[]
}

export interface GradePrediction {
  id: number
  user_id: number
  quiz_attempt_id: number
  level_id: number
  score_percent: string
  predicted_grade: string
  created_at: string
}

export interface Badge {
  code: string
  name: string
  description: string | null
  icon: string | null
  awarded_at: string
}

export interface GamificationSummary {
  total_points: number
  current_streak_days: number
  longest_streak_days: number
  badges: Badge[]
  recent_points: { points: number; reason: string; created_at: string }[]
}

export interface SchoolClass {
  id: number
  teacher_id: number
  level_id: number
  name: string
  join_code: string
  level?: Level
  teacher?: { id: number; name: string }
  class_students_count?: number
  class_students?: { student: { id: number; name: string; email: string } }[]
}

export interface GroupMessage {
  id: number
  content: string
  created_at: string
  user: { id: number; name: string }
}

export interface PeerGroup {
  id: number
  name: string
  level_id: number
  join_code: string
  created_by: number
}

export interface GroupChallenge {
  id: number
  peer_group_id: number
  topic_id: number | null
  starts_at: string
  ends_at: string
  question_count: number
  status: string
  topic?: Topic
}

export interface SubscriptionPlan {
  id: number
  code: 'free' | 'premium' | 'premium_plus'
  name: string
  price_xaf: number
  billing_interval: string
  features: {
    topic_quizzes_per_day: number | null
    gce_simulations_per_month: number | null
    study_plan: boolean
    parent_email: boolean
    peer_groups: boolean
    teacher_analytics: boolean
    paper_marking: boolean
    ai_coach_messages_per_day: number | null
  }
}

export interface Subscription {
  id: number
  status: string
  started_at: string
  ends_at: string | null
  plan: SubscriptionPlan
}

export interface ParentContact {
  id: number
  student_id: number
  name: string
  email: string
  phone_number: string | null
  relationship: string | null
  locale: string
  is_active: boolean
}

export type LearningResourceType = 'video' | 'pdf' | 'notes' | 'exercise' | 'mock_paper' | 'past_paper'

export interface ResourceRecommendationGroup {
  topic_id: number
  topic_name: string
  resources: LearningResource[]
}

export interface LearningResource {
  id: number
  topic_id: number
  level_id: number
  created_by: number
  title: string
  description: string | null
  type: LearningResourceType
  url: string | null
  file_path: string | null
  resource_url: string | null
  status: 'pending' | 'approved' | 'rejected'
  reviewed_by: number | null
  reviewed_at: string | null
  rejection_reason: string | null
  created_at: string
  topic?: { id: number; name: string; code: string | null }
  creator?: { id: number; name: string; email: string }
}
