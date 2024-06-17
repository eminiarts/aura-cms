<x-aura::layout.guest>
    <div class="mb-4 text-sm text-gray-600">
        {{ __('Thanks for signing up! Before getting started, could you verify your email address by clicking on the link we just emailed to you? If you didn\'t receive the email, we will gladly send you another.') }}
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-4 text-sm font-medium text-green-600">
            {{ __('A new verification link has been sent to the email address you provided during registration.') }}
        </div>
    @endif

    <div class="flex justify-between items-center mt-4">
        <form method="POST" action="{{ route('aura.verification.send') }}">
            @csrf
            <div class="flex justify-end items-center">
                <x-aura::button id="register-button" type="submit" block>{{ __('Resend Verification Email') }}</x-aura::button>
            </div>
        </form>

        <form method="POST" action="{{ route('aura.logout') }}">
            @csrf

            <button type="submit" class="text-sm text-gray-600 underline rounded-md hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                {{ __('Log Out') }}
            </button>
        </form>
    </div>
</x-aura::layout.guest>
