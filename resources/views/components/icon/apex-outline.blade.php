{{-- One Λ of the wordmark (its last path) as a 1px outline, no fill. Cropped by its container as a
     quiet backdrop: the auth brand panel and the page-level empty table. Color it with a stroke-*
     class; callers hide it when a custom logo replaces Aura's wordmark. --}}
<svg {{ $attributes->merge(['aria-hidden' => 'true']) }} viewBox="95 0 28 24" fill="none" xmlns="http://www.w3.org/2000/svg">
    <path d="M122.214 23.7945H115.914L111.327 15.7146L108.827 11.1611L106.328 15.7146L103.418 20.7817L101.74 23.8287L95.4406 23.7945L108.827 0.17111L122.214 23.7945Z" stroke-width="1" vector-effect="non-scaling-stroke" stroke-linejoin="miter" />
</svg>
