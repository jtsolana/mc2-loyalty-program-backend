<x-mail::message>
# Support Request

**From:** {{ $user->name }} ({{ $user->email }})

**Subject:** {{ $userSubject }}

---

{{ $userMessage }}

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
