@php
    // Some auth flows (Breeze defaults) flash a slug into `status` rather than
    // human-readable text, because the same view previously chose the wording
    // itself. Centralising the toast means we need to translate those slugs
    // here instead.
    $statusSlugs = [
        'verification-link-sent' => 'A new verification link has been sent to your email address.',
        'password-updated' => 'Password updated.',
        'profile-updated' => 'Profile updated.',
    ];

    $status = session('status');

    $toasts = array_filter([
        'success' => $status ? ($statusSlugs[$status] ?? $status) : session('success'),
        'error' => session('error'),
        'warning' => session('warning'),
        'info' => session('info'),
    ]);
@endphp

@if (!empty($toasts))
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            @foreach ($toasts as $type => $message)
                toastr.{{ $type }}(@json($message));
            @endforeach
        });
    </script>
@endif
