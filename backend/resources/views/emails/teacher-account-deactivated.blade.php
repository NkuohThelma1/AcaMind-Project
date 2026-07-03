<x-mail::message>
# Your Teacher Account Has Been Deactivated

Hello {{ $teacherName }},

Your AcaMind teacher account has been automatically deactivated after {{ $inactivityDays }} days without any
activity.

If you'd like to keep teaching on AcaMind, please submit a reactivation request and our admin team will review
it and restore your access.

<x-mail::button :url="config('app.frontend_url', config('app.url')) . '/reactivation-request'">
Request Reactivation
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
