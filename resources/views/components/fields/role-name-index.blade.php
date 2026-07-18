<div class="flex items-center gap-x-2">
    <span>{{ $value }}</span>

    @if (config('aura.teams') && is_null($row->team_id))
        <span
            class="inline-flex items-center whitespace-nowrap rounded-full bg-primary-50 px-2 py-0.5 text-xs font-medium text-primary-700 ring-1 ring-inset ring-primary-600/20 dark:bg-primary-500/10 dark:text-primary-300 dark:ring-primary-400/30"
            title="{{ __('Global Role — part of the shared catalog. Read-only in this team.') }}">
            {{ __('Global') }}
        </span>
    @endif
</div>
