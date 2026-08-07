<x-mail::message>
# Password Reset

You are receiving this email because a password reset was requested for your account.

Your verification code is:

# {{ $otp }}

This code is **valid for only 5 seconds**.

Do not share this code with anyone.

If you did not request a password reset, no further action is required.

Regards,<br>
{{ config('app.name') }}
</x-mail::message>
