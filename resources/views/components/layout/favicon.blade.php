@php
    $appSettings = app('aura')::options();

    $favicon = $appSettings['theme']['app-favicon'] ?? '/vendor/aura/public/favicon-32x32.png';
    $darkFavicon = $appSettings['theme']['app-favicon-darkmode'] ?? '/vendor/aura/public/favicon-darkmode-32x32.png';

    // If dark favicon is not set, use the light favicon
    $darkFavicon = $darkFavicon ?: $favicon;
@endphp

<link rel="icon" type="image/png" sizes="32x32" href="{{ $favicon }}" id="favicon">

<script>
    const darkModeMediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
    const favicon = document.getElementById('favicon');

    function setFaviconBasedOnPreferredColorScheme(event) {
        console.log('event', event.matches);
        favicon.href = event.matches ? '{{ $darkFavicon }}' : '{{ $favicon }}';
    }

    darkModeMediaQuery.addListener(setFaviconBasedOnPreferredColorScheme);

    // Set the initial value
    document.addEventListener('DOMContentLoaded', () => {
        setFaviconBasedOnPreferredColorScheme(darkModeMediaQuery);
    });

    // Update favicon when navigating with Livewire
    document.addEventListener('livewire:navigated', () => {
        setFaviconBasedOnPreferredColorScheme(darkModeMediaQuery);
    });
</script>
