<x-aura::jet-form-section submit="updateProfileInformation">
    <x-slot name="title">
        {{ __('Profile Information') }}
    </x-slot>

    <x-slot name="description">
        {{ __('Update your account\'s profile information and email address.') }}
    </x-slot>

    <x-slot name="form">
        <!-- Profile Photo -->
        @if (Laravel\Jetstream\Jetstream::managesProfilePhotos())
            <div x-data="{photoName: null, photoPreview: null}" class="col-span-6 sm:col-span-4">
                <!-- Profile Photo File Input -->
                <input type="file" class="hidden"
                            wire:model="photo"
                            x-ref="photo"
                            x-on:change="
                                    photoName = $refs.photo.files[0].name;
                                    const reader = new FileReader();
                                    reader.onload = (e) => {
                                        photoPreview = e.target.result;
                                    };
                                    reader.readAsDataURL($refs.photo.files[0]);
                            " />

                <x-aura::label for="photo" value="{{ __('Photo') }}" />

                <!-- Current Profile Photo -->
                <div class="mt-2" x-show="! photoPreview">
                    <img src="{{ $this->user->profile_photo_url }}" alt="{{ $this->user->name }}" class="object-cover w-20 h-20 rounded-full">
                </div>

                <!-- New Profile Photo Preview -->
                <div class="mt-2" x-show="photoPreview" nonce="{{ csp_nonce() }}" style="display: none;">
                    <span class="block w-20 h-20 bg-center bg-no-repeat bg-cover rounded-full" nonce="{{ csp_nonce() }}"
                          x-bind:style="'background-image: url(\'' + photoPreview + '\');'">
                    </span>
                </div>

                <x-aura::jet-secondary-button class="mt-2 mr-2" type="button" x-on:click.prevent="$refs.photo.click()">
                    {{ __('Select A New Photo') }}
                </x-aura::jet-secondary-button>

                @if ($this->user->profile_photo_path)
                    <x-aura::jet-secondary-button type="button" class="mt-2" wire:click="deleteProfilePhoto">
                        {{ __('Remove Photo') }}
                    </x-aura::jet-secondary-button>
                @endif

                <x-aura::jet-input-error for="photo" class="mt-2" />
            </div>
        @endif

        <!-- Name -->
        <div class="col-span-6 sm:col-span-4">
            <x-aura::label for="name" value="{{ __('Name') }}" />
            <x-aura::jet-input id="name" type="text" class="block mt-1 w-full" wire:model.defer="state.name" autocomplete="name" />
            <x-aura::jet-input-error for="name" class="mt-2" />
        </div>

        <!-- Email -->
        <div class="col-span-6 sm:col-span-4">
            <x-aura::label for="email" value="{{ __('Email') }}" />
            <x-aura::jet-input id="email" type="email" class="block mt-1 w-full" wire:model.defer="state.email" />
            <x-aura::jet-input-error for="email" class="mt-2" />
        </div>
    </x-slot>

    <x-slot name="actions">
        <x-aura::jet-action-message class="mr-3" on="saved">
            {{ __('Saved.') }}
        </x-aura::jet-action-message>

        <x-aura::jet-button wire:loading.attr="disabled" wire:target="photo">
            {{ __('Save') }}
        </x-aura::jet-button>
    </x-slot>
</x-aura::jet-form-section>
