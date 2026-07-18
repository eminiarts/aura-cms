<div class="w-full" wire:key="user-teams-{{ $userId }}">
    <div class="p-4 bg-white rounded-lg shadow dark:bg-gray-800">
        <h3 class="mb-4 text-lg font-semibold text-gray-900 dark:text-gray-100">
            {{ __('Team Memberships') }}
        </h3>

        @if (count($this->memberships))
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left" wire:key="memberships-table">
                    <thead class="text-xs text-gray-500 uppercase border-b dark:text-gray-400">
                        <tr>
                            <th class="px-3 py-2">{{ __('Team') }}</th>
                            <th class="px-3 py-2">{{ __('Role') }}</th>
                            <th class="px-3 py-2 text-right">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->memberships as $membership)
                            <tr class="border-b dark:border-gray-700" wire:key="membership-{{ $membership['team_id'] }}">
                                <td class="px-3 py-2 font-medium text-gray-900 dark:text-gray-100">
                                    {{ $membership['team_name'] }}
                                </td>
                                <td class="px-3 py-2">
                                    @if ($membership['can_manage'])
                                        <select
                                            wire:model="roleSelections.{{ $membership['team_id'] }}"
                                            wire:change="changeRole({{ $membership['team_id'] }})"
                                            dusk="role-select-{{ $membership['team_id'] }}"
                                            class="text-sm border-gray-300 rounded-md dark:bg-gray-700 dark:border-gray-600"
                                        >
                                            @foreach ($membership['role_options'] as $roleId => $roleName)
                                                <option value="{{ $roleId }}">{{ $roleName }}</option>
                                            @endforeach
                                        </select>
                                    @else
                                        <span class="text-gray-700 dark:text-gray-300" dusk="role-readonly-{{ $membership['team_id'] }}">
                                            {{ $membership['role_name'] ?? '–' }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-3 py-2 text-right">
                                    @if ($membership['can_manage'])
                                        <x-aura::button.transparent
                                            wire:click="detach({{ $membership['team_id'] }})"
                                            dusk="detach-{{ $membership['team_id'] }}"
                                            class="text-red-500 hover:text-red-700"
                                        >
                                            {{ __('Remove') }}
                                        </x-aura::button.transparent>
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
            <div class="pt-4 mt-6 border-t dark:border-gray-700" wire:key="attach-form">
                <h4 class="mb-2 text-sm font-semibold text-gray-900 dark:text-gray-100">
                    {{ __('Add to a team') }}
                </h4>
                <div class="flex flex-wrap items-start gap-2">
                    <div>
                        <select
                            wire:model.live="attachTeamId"
                            dusk="attach-team"
                            class="text-sm border-gray-300 rounded-md dark:bg-gray-700 dark:border-gray-600"
                        >
                            <option value="">{{ __('Select a team') }}</option>
                            @foreach ($this->teamOptions as $teamId => $teamName)
                                <option value="{{ $teamId }}">{{ $teamName }}</option>
                            @endforeach
                        </select>
                        @error('attachTeamId')
                            <p class="mt-1 text-xs text-red-500" dusk="attach-team-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <select
                            wire:model="attachRoleId"
                            dusk="attach-role"
                            class="text-sm border-gray-300 rounded-md dark:bg-gray-700 dark:border-gray-600"
                        >
                            <option value="">{{ __('Select a role') }}</option>
                            @foreach ($this->attachRoleOptions as $roleId => $roleName)
                                <option value="{{ $roleId }}">{{ $roleName }}</option>
                            @endforeach
                        </select>
                        @error('attachRoleId')
                            <p class="mt-1 text-xs text-red-500" dusk="attach-role-error">{{ $message }}</p>
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
