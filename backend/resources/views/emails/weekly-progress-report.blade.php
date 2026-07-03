<x-mail::message>
# Weekly Progress Report

Hello,

Here is the weekly AcaMind update for **{{ $report->student->name }}**
({{ $report->period_start->format('M j') }} – {{ $report->period_end->format('M j') }}):

{{ $report->content }}

<x-mail::button :url="config('app.frontend_url', config('app.url'))">
View AcaMind
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
