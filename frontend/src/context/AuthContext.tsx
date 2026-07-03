import { createContext, useCallback, useContext, useEffect, useState, type ReactNode } from 'react'
import { apiClient, TOKEN_STORAGE_KEY } from '../api/client'
import type { User } from '../types'

interface RegisterPayload {
  name: string
  email: string
  password: string
  password_confirmation: string
  role: 'student' | 'teacher_pending'
  phone?: string
  level_id?: number
}

interface TeacherRegisterDocuments {
  national_id: File
  degree_certificate: File
  teaching_qualification: File
  cv: File
}

interface AuthContextValue {
  user: User | null
  isLoading: boolean
  login: (email: string, password: string) => Promise<void>
  register: (payload: RegisterPayload, documents?: TeacherRegisterDocuments) => Promise<void>
  logout: () => Promise<void>
  refreshUser: () => Promise<void>
}

const AuthContext = createContext<AuthContextValue | undefined>(undefined)

export function AuthProvider({ children }: { children: ReactNode }) {
  const [user, setUser] = useState<User | null>(null)
  const [isLoading, setIsLoading] = useState(true)

  const refreshUser = useCallback(async () => {
    const token = localStorage.getItem(TOKEN_STORAGE_KEY)
    if (!token) {
      setUser(null)
      setIsLoading(false)
      return
    }
    try {
      const { data } = await apiClient.get<User>('/auth/me')
      setUser(data)
    } catch {
      localStorage.removeItem(TOKEN_STORAGE_KEY)
      setUser(null)
    } finally {
      setIsLoading(false)
    }
  }, [])

  useEffect(() => {
    refreshUser()
  }, [refreshUser])

  const login = useCallback(async (email: string, password: string) => {
    const { data } = await apiClient.post('/auth/login', { email, password })
    localStorage.setItem(TOKEN_STORAGE_KEY, data.token)
    setUser(data.user)
  }, [])

  const register = useCallback(async (payload: RegisterPayload, documents?: TeacherRegisterDocuments) => {
    if (documents) {
      const formData = new FormData()
      Object.entries(payload).forEach(([key, value]) => {
        if (value !== undefined) formData.append(key, String(value))
      })
      Object.entries(documents).forEach(([key, file]) => formData.append(key, file))

      const { data } = await apiClient.post('/auth/register', formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
      })
      localStorage.setItem(TOKEN_STORAGE_KEY, data.token)
      setUser(data.user)
      return
    }

    const { data } = await apiClient.post('/auth/register', payload)
    localStorage.setItem(TOKEN_STORAGE_KEY, data.token)
    setUser(data.user)
  }, [])

  const logout = useCallback(async () => {
    try {
      await apiClient.post('/auth/logout')
    } finally {
      localStorage.removeItem(TOKEN_STORAGE_KEY)
      setUser(null)
    }
  }, [])

  return (
    <AuthContext.Provider value={{ user, isLoading, login, register, logout, refreshUser }}>
      {children}
    </AuthContext.Provider>
  )
}

export function useAuth(): AuthContextValue {
  const ctx = useContext(AuthContext)
  if (!ctx) {
    throw new Error('useAuth must be used within an AuthProvider')
  }
  return ctx
}
