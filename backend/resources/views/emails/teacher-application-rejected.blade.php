<x-mail::message>
# Your Teacher Application

Hello {{ $applicantName }},

Thank you for applying to teach on AcaMind. After reviewing your submitted documents, we're unable to approve
your application at this time.

@if($reason)
**Feedback from our review:**

{{ $reason }}
@endif

Teaching positions on AcaMind are granted based on the qualifications and documents provided, so we encourage
you to review and correct your National ID, Highest Degree Certificate, Teaching Qualification Certificate, and
CV/Resume, and apply again.

<x-mail::button :url="config('app.frontend_url', config('app.url')) . '/register'">
Apply Again
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
