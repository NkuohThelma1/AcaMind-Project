import { Outlet } from 'react-router-dom'
import { ProtectedRoute } from './ProtectedRoute'
import { Layout } from './Layout'

export function AppShell() {
  return (
    <ProtectedRoute>
      <Layout>
        <Outlet />
      </Layout>
    </ProtectedRoute>
  )
}
