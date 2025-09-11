<x-mail::message>
# Your export for {{ $organizationName }} is ready

<x-mail::button :url="$link">
Download your export
</x-mail::button>

This link will only be valid for the next 24 hours. After that, it will expire and no longer be accessible, and you will need to request a new export.

<x-mail::panel>
If you did not request this link, make sure to visit your account and change your password, just in case.
</x-mail::panel>

Thanks,<br>
{{ config('app.name') }}

</x-mail::message>
