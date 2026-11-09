<div class="flex items-center gap-x-2">
    <span>{{ $value }}</span>

    @if (config('aura.teams') && is_null($row->team_id))
        <span
            class="aura-tag aura-tag-status bg-primary-50 text-primary-700 dark:bg-primary-500/10 dark:text-primary-300"
            title="{{ __('Global Role — part of the shared catalog. Read-only in this team.') }}">
            {{ __('Global') }}
        </span>
    @endif
</div>
