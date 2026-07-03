import { createBrowserRouter, createRoutesFromElements, Route } from 'react-router-dom'
import { useAuth } from './context/AuthContext'
import { AppShell } from './components/AppShell'
import { LandingPage } from './pages/LandingPage'
import { LoginPage } from './pages/LoginPage'
import { RegisterPage } from './pages/RegisterPage'
import { StudentDashboardPage } from './pages/StudentDashboardPage'
import { TeacherDashboardPage } from './pages/TeacherDashboardPage'
import { ClassAnalyticsPage } from './pages/ClassAnalyticsPage'
import { AdminTeacherVerificationPage } from './pages/AdminTeacherVerificationPage'
import { PendingApprovalPage } from './pages/PendingApprovalPage'
import { QuizRunnerPage } from './pages/QuizRunnerPage'
import { QuizResultPage } from './pages/QuizResultPage'
import { StudyPlanPage } from './pages/StudyPlanPage'
import { PredictionsPage } from './pages/PredictionsPage'
import { PeerGroupsPage } from './pages/PeerGroupsPage'
import { PeerGroupDetailPage } from './pages/PeerGroupDetailPage'
import { SubscriptionPage } from './pages/SubscriptionPage'
import { ParentContactsPage } from './pages/ParentContactsPage'
import { TeacherResourcesPage } from './pages/TeacherResourcesPage'
import { AiCoachPage } from './pages/AiCoachPage'
import { ReactivationRequestPage } from './pages/ReactivationRequestPage'
import { StudentClassesPage } from './pages/StudentClassesPage'
import { StudentClassDetailPage } from './pages/StudentClassDetailPage'

function HomeRouter() {
  const { user } = useAuth()

  if (!user) return null

  if (user.role === 'student') return <StudentDashboardPage />
  if (user.role === 'teacher_verified') return <TeacherDashboardPage />
  if (user.role === 'admin') return <AdminTeacherVerificationPage />
  return <PendingApprovalPage />
}

function ClassRouter() {
  const { user } = useAuth()

  if (!user) return null

  if (user.role === 'teacher_verified') return <ClassAnalyticsPage />
  return <StudentClassDetailPage />
}

export const router = createBrowserRouter(
  createRoutesFromElements(
    <>
      <Route path="/" element={<LandingPage />} />
      <Route path="/login" element={<LoginPage />} />
      <Route path="/register" element={<RegisterPage />} />
      <Route path="/reactivation-request" element={<ReactivationRequestPage />} />

      <Route element={<AppShell />}>
        <Route path="/dashboard" element={<HomeRouter />} />
        <Route path="/quiz/:attemptId" element={<QuizRunnerPage />} />
        <Route path="/quiz/:attemptId/result" element={<QuizResultPage />} />
        <Route path="/study-plan" element={<StudyPlanPage />} />
        <Route path="/predictions" element={<PredictionsPage />} />
        <Route path="/peer-groups" element={<PeerGroupsPage />} />
        <Route path="/peer-groups/:groupId" element={<PeerGroupDetailPage />} />
        <Route path="/subscription" element={<SubscriptionPage />} />
        <Route path="/parent-contacts" element={<ParentContactsPage />} />
        <Route path="/resources" element={<TeacherResourcesPage />} />
        <Route path="/ai-coach" element={<AiCoachPage />} />
        <Route path="/my-classes" element={<StudentClassesPage />} />
        <Route path="/classes/:classId" element={<ClassRouter />} />
      </Route>
    </>,
  ),
)
