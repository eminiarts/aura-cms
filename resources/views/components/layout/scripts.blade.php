@stack('scripts')
{{-- @vite(['resources/js/app.js'], 'vendor/aura') --}}

{{ app('aura')::viteScripts() }}
@vite(['resources/js/apexcharts.js'], 'vendor/aura/libs')
