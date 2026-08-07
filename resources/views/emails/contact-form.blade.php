<x-mail::message>
# New Contact Message

You received a new contact message from the {{ config('app.name') }} website.

<x-mail::table>
| Field | Value |
|:------|:------|
| **Name** | {{ $name }} |
| **Email** | {{ $email }} |
| **Company** | {{ $company ?? '-' }} |
| **Topic** | {{ $topic }} |
</x-mail::table>

**Message:**

{{ $message }}

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
