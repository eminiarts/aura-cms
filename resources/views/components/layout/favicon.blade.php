@php
    use Aura\Base\Resources\Attachment;

    // $appSettings = app('aura')::options();
    $favicon = $darkFavicon = false;

    if(isset($appSettings['app-favicon'])) {
        $appFavicon = $appSettings['app-favicon'];
        $favicon = optional(Attachment::find($appFavicon)->first())->path();
        $darkFavicon = $favicon;
    }

    if(isset($appSettings['app-favicon-darkmode'])) {
        $appFaviconDark = $appSettings['app-favicon-darkmode'];
        $darkFavicon = optional(Attachment::find($appFaviconDark)->first())->path();
    }

    if (!$favicon && $darkFavicon) {
        $favicon = $darkFavicon;
    }

    if (!$favicon) {
        $favicon = '/vendor/aura/public/favicon-32x32.png';
    }

    if (!$darkFavicon) {
        $darkFavicon = '/vendor/aura/public/favicon-darkmode-32x32.png';
    }

@endphp

<link rel="icon" type="image/png" sizes="32x32" href="{{ $favicon }}">

<script>
    const darkModeMediaQuery = window.matchMedia('(prefers-color-scheme: dark)');

    function setFaviconBasedOnPreferredColorScheme(event) {
    if (event.matches) {
        // The user has set their browser to prefer dark mode, so show the darkmode favicon
        document.querySelector("link[sizes='32x32']").href = '{{ $darkFavicon }}';
    } else {
        // The user has set their browser to prefer light mode, so show the lightmode favicon
        document.querySelector("link[sizes='32x32']").href = '{{ $favicon }}';
    }
    }

    darkModeMediaQuery.addListener(setFaviconBasedOnPreferredColorScheme);

    // Set the initial value
    document.addEventListener('livewire:navigated', () => {
        setFaviconBasedOnPreferredColorScheme(darkModeMediaQuery);
    });
</script>
