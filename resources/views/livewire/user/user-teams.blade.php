{{-- Same look as a relation table on a record page: section title, sentence-case headings, hairlines, no card. --}}
<div class="w-full" wire:key="user-teams-{{ $userId }}">
    <div>
        <h3 class="mb-3 aura-section-title">
            {{ __('Team Memberships') }}
        </h3>

        @if (count($this->memberships))
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm text-left" wire:key="memberships-table">
                    <thead>
                        <tr class="border-b border-gray-200/80 dark:border-white/10">
                            <x-aura::table.heading>{{ __('Team') }}</x-aura::table.heading>
                            <x-aura::table.heading>{{ __('Role') }}</x-aura::table.heading>
                            <x-aura::table.heading align="right">{{ __('Actions') }}</x-aura::table.heading>
                        </tr>
                    </thead>
                    <tbody class="border-b divide-y divide-gray-200/60 border-gray-200/80 dark:divide-white/[0.06] dark:border-white/10">
                        @foreach ($this->memberships as $membership)
                            <tr wire:key="membership-{{ $membership['team_id'] }}">
                                <td class="px-4 font-medium text-gray-900 aura-table-cell dark:text-white">
                                    {{ $membership['team_name'] }}
                                </td>
                                <td class="px-4 aura-table-cell">
                                    @if ($membership['can_manage'])
                                        <div class="w-48">
                                            <x-aura::input.select
                                                size="sm"
                                                :name="'role-'.$membership['team_id']"
                                                :options="$membership['role_options']"
                                                :aria-label="__('Role in :team', ['team' => $membership['team_name']])"
                                                wire:model="roleSelections.{{ $membership['team_id'] }}"
                                                wire:change="changeRole({{ $membership['team_id'] }})"
                                                dusk="role-select-{{ $membership['team_id'] }}"
                                            />
                                        </div>
                                    @else
                                        <span class="text-gray-700 dark:text-gray-300" dusk="role-readonly-{{ $membership['team_id'] }}">
                                            {{ $membership['role_name'] ?? '–' }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 text-right aura-table-cell">
                                    @if ($membership['can_manage'])
                                        <x-aura::button.danger
                                            size="sm"
                                            wire:click="detach({{ $membership['team_id'] }})"
                                            dusk="detach-{{ $membership['team_id'] }}"
                                        >
                                            {{ __('Remove') }}
                                        </x-aura::button.danger>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-sm text-gray-500 dark:text-gray-400" dusk="no-memberships">
                {{ __('This user is not a member of any team.') }}
            </p>
        @endif

        @if (count($this->teamOptions))
            <div class="mt-6" wire:key="attach-form">
                <h4 class="mb-2 text-sm font-semibold text-gray-900 dark:text-gray-100">
                    {{ __('Add to a team') }}
                </h4>
                <div class="flex flex-wrap items-start gap-2">
                    <div class="w-56">
                        <x-aura::input.select
                            name="attachTeamId"
                            error="attachTeamId"
                            :options="$this->teamOptions"
                            :placeholder="__('Select a team')"
                            :aria-label="__('Team')"
                            wire:model.live="attachTeamId"
                            dusk="attach-team"
                        />
                        @error('attachTeamId')
                            <p class="mt-1 text-sm aura-field-error" dusk="attach-team-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="w-56">
                        <x-aura::input.select
                            name="attachRoleId"
                            error="attachRoleId"
                            :options="$this->attachRoleOptions"
                            :placeholder="__('Select a role')"
                            :aria-label="__('Role')"
                            wire:model="attachRoleId"
                            dusk="attach-role"
                        />
                        @error('attachRoleId')
                            <p class="mt-1 text-sm aura-field-error" dusk="attach-role-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <x-aura::button.primary wire:click="attach" dusk="attach-submit">
                        {{ __('Add') }}
                    </x-aura::button.primary>
                </div>
            </div>
        @endif
    </div>
</div>
