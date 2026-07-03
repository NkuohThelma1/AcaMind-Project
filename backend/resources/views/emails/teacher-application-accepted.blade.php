<x-mail::message>
# Congratulations, {{ $applicantName }}!

Your AcaMind teacher application has passed our document review. The next step is a short interview with our
admin team.

**Interview date & time:** {{ $interviewAt->format('l, F j, Y \a\t g:i A') }}

Please join at the scheduled time using the link below:

<x-mail::button :url="$meetLink">
Join Google Meet
</x-mail::button>

If the link above doesn't work, copy and paste this into your browser:

{{ $meetLink }}

We look forward to speaking with you.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
