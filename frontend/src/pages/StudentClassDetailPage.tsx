import { useEffect, useState } from 'react'
import { useParams } from 'react-router-dom'
import { apiClient } from '../api/client'
import { GroupChat } from '../components/GroupChat'
import type { SchoolClass } from '../types'

export function StudentClassDetailPage() {
  const { classId } = useParams()
  const [schoolClass, setSchoolClass] = useState<SchoolClass | null>(null)

  useEffect(() => {
    apiClient.get<SchoolClass>(`/classes/${classId}`).then(({ data }) => setSchoolClass(data))
  }, [classId])

  if (!schoolClass) return <p className="text-sm text-slate-500">Loading class...</p>

  return (
    <div className="mx-auto max-w-2xl">
      <h1 className="mb-1 text-2xl font-bold text-slate-900">{schoolClass.name}</h1>
      <p className="mb-6 text-sm text-slate-500">
        {schoolClass.level?.name} · taught by {schoolClass.teacher?.name} ·{' '}
        {schoolClass.class_students?.length ?? 0} classmate(s)
      </p>

      <GroupChat kind="classes" id={classId!} />
    </div>
  )
}
