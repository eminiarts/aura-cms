<div class="mt-10 sm:mt-0">
    <div class="md:grid md:grid-cols-3 md:gap-6">
        <div class="md:col-span-1">
            <div class="px-4 sm:px-0">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ __('Team Members') }}</h3>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    {{ __('All of the people that are part of this team.') }}
                </p>
            </div>
        </div>

        <div class="mt-5 md:mt-0 md:col-span-2">
            <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <div class="px-4 py-5 sm:p-6">

                    {{-- Add Team Member Button --}}
                    @can('invite-users', $team)
                        <div class="flex justify-end mb-4">
                            <x-aura::button.primary wire:click="openInviteModal">
                                {{ __('Invite Member') }}
                            </x-aura::button.primary>
                        </div>
                    @endcan

                    {{-- Current Members --}}
                    <div class="space-y-3">
                        @foreach ($this->members as $member)
                            <div class="flex items-center justify-between py-3 {{ !$loop->last ? 'border-b border-gray-200 dark:border-gray-700' : '' }}">
                                <div class="flex items-center">
                                    <div>
                                        <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                            {{ $member->name }}
                                        </div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400">
                                            {{ $member->email }}
                                        </div>
                                    </div>
                                </div>

                                <div class="flex items-center space-x-3">
                                    {{-- Role Badge/Selector --}}
                                    @if ($team->user_id === $member->id)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary-100 text-primary-800 dark:bg-primary-800 dark:text-primary-100">
                                            {{ __('Owner') }}
                                        </span>
                                    @else
                                        @can('updateTeamMember', $team)
                                            <select wire:change="updateMemberRole({{ $member->id }}, $event.target.value)"
                                                class="text-sm border-gray-300 rounded-md shadow-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-primary-500 focus:ring-primary-500">
                                                @foreach ($this->roles as $role)
                                                    <option value="{{ $role->id }}" {{ optional($member->pivot)->role_id == $role->id ? 'selected' : '' }}>
                                                        {{ $role->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        @else
                                            @php
                                                $memberRole = $this->roles->firstWhere('id', optional($member->pivot)->role_id);
                                            @endphp
                                            @if ($memberRole)
                                                <span class="text-sm text-gray-500 dark:text-gray-400">{{ $memberRole->name }}</span>
                                            @endif
                                        @endcan

                                        {{-- Remove Button --}}
                                        @can('removeTeamMember', $team)
                                            <button wire:click="removeMember({{ $member->id }})"
                                                wire:confirm="{{ __('Are you sure you want to remove this team member?') }}"
                                                class="text-sm text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300">
                                                {{ __('Remove') }}
                                            </button>
                                        @endcan
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- Pending Invitations --}}
                    @if ($this->invitations->isNotEmpty())
                        <div class="mt-6">
                            <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-3">{{ __('Pending Invitations') }}</h4>
                            <div class="space-y-3">
                                @foreach ($this->invitations as $invitation)
                                    <div class="flex items-center justify-between py-3 {{ !$loop->last ? 'border-b border-gray-200 dark:border-gray-700' : '' }}">
                                        <div class="text-sm text-gray-600 dark:text-gray-400">
                                            {{ $invitation->email ?? optional($invitation)->getMeta('email') }}
                                        </div>

                                        @can('removeTeamMember', $team)
                                            <button wire:click="cancelInvitation({{ $invitation->id }})"
                                                class="text-sm text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300">
                                                {{ __('Cancel') }}
                                            </button>
                                        @endcan
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Invite User Modal --}}
    @if ($showInviteModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75 dark:bg-gray-900 dark:bg-opacity-75" wire:click="$set('showInviteModal', false)"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div class="inline-block overflow-hidden text-left align-bottom transition-all transform bg-white dark:bg-gray-800 rounded-lg shadow-xl sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    @livewire('aura::invite-user')
                </div>
            </div>
        </div>
    @endif
</div>
