<x-mail::message>
# Teacher Reactivation Request

**Teacher:** {{ $teacherName }}<br>
**Email:** {{ $teacherEmail }}

**Message:**

{{ $message }}

You can reply directly to this email to respond to {{ $teacherName }}, or reactivate their account from the
Admin dashboard.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
