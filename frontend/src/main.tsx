import { StrictMode } from 'react'
import { createRoot } from 'react-dom/client'
import { RouterProvider } from 'react-router-dom'
import './index.css'
import { router } from './router.tsx'
import { AuthProvider } from './context/AuthContext.tsx'
import { QuizSessionProvider } from './context/QuizSessionContext.tsx'

createRoot(document.getElementById('root')!).render(
  <StrictMode>
    <AuthProvider>
      <QuizSessionProvider>
        <RouterProvider router={router} />
      </QuizSessionProvider>
    </AuthProvider>
  </StrictMode>,
)
