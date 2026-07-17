<x-aura::layout.guest>

    <div class="mb-6">
        <h1 class="text-2xl font-semibold tracking-tight text-gray-900 dark:text-white">{{ __('Verify your email') }}</h1>
        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
            {{ __('Thanks for signing up! Before getting started, could you verify your email address by clicking on the link we just emailed to you? If you didn\'t receive the email, we will gladly send you another.') }}
        </p>
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="flex gap-3 items-start p-4 mb-6 text-sm text-green-800 bg-green-50 rounded-lg ring-1 ring-inset ring-green-600/10 dark:bg-green-500/10 dark:text-green-300 dark:ring-green-500/20">
            <svg class="w-5 h-5 shrink-0 text-green-600 dark:text-green-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.857-9.809a.75.75 0 0 0-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z" clip-rule="evenodd" />
            </svg>
            <p class="font-medium">{{ __('A new verification link has been sent to the email address you provided during registration.') }}</p>
        </div>
    @endif

    <form method="POST" action="{{ route('aura.verification.send') }}">
        @csrf
        <x-aura::button id="register-button" type="submit" block>{{ __('Resend Verification Email') }}</x-aura::button>
    </form>

    <form method="POST" action="{{ route('aura.logout') }}" class="mt-6 text-center">
        @csrf

        <button type="submit" class="text-sm font-medium rounded-md text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 dark:focus:ring-offset-gray-900">
            {{ __('Log Out') }}
        </button>
    </form>
</x-aura::layout.guest>
