{{-- Initials for a user without an image. With $tone (User::displayAvatar() passes avatarTone()) it is the
     40px entity avatar in the record's tint, the same as the header avatar; without, the primary tint. --}}
<div class="flex items-center space-x-2">
    <div
        @class([
            'aura-avatar aura-avatar-lg aura-avatar-circle aura-avatar-tone-'.($tone ?? '') => filled($tone ?? null),
            'flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-primary-100 text-sm font-semibold tracking-wide text-primary-800 shadow-sm ring-1 ring-primary-600/20 dark:bg-primary-400/10 dark:text-primary-300 dark:ring-primary-400/30' => blank($tone ?? null),
        ])
        role="img"
        aria-label="{{ __('Avatar for :name', ['name' => $name]) }}"
    >
        {{ $initials }}
    </div>
</div>
