export function PendingApprovalPage() {
  return (
    <div className="mx-auto max-w-md rounded-xl border border-slate-200 bg-white p-8 text-center">
      <h1 className="mb-2 text-xl font-bold text-slate-900">Application under review</h1>
      <p className="mb-3 text-sm text-slate-500">
        Thanks for applying to teach on AcaMind. Our admin team reviews your submitted documents (National ID,
        Degree Certificate, Teaching Qualification, and CV) and responds within <strong>3 days</strong>.
      </p>
      <p className="text-sm text-slate-500">
        Please log back into this account within 3 days to check your status: if you land on the teacher
        dashboard, you've been accepted and invited to interview by email. If you're unable to log in, your
        application wasn't successful this time - check the email you registered with for feedback, and you're
        welcome to apply again.
      </p>
    </div>
  )
}
