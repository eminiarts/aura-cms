<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center gap-2 h-9 px-3.5 text-sm font-medium text-white bg-primary-600 rounded-lg shadow-button hover:bg-primary-500 active:bg-primary-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-gray-900 disabled:opacity-50 disabled:pointer-events-none select-none whitespace-nowrap transition-colors duration-150']) }}>
    {{ $slot }}
</button>
