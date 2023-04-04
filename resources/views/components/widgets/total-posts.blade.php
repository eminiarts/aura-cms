<div class="aura-card">
  <div class="p-2">
    <div class="flex items-baseline justify-between mb-4">
      <span class="text-sm font-semibold">{{ $widget['name'] }}</span>

      <div class="ml-4">
        <span class="text-xs font-normal text-gray-500">{{ $start }} - {{ $end }}</span>
      </div>
    </div>

    <div class="flex items-baseline justify-between mt-1 mb-2 md:block lg:flex">
      <div class="flex items-baseline text-4xl font-medium">
        {{ $this->values['current'] ?? 'N/A' }}
      </div>

      <div>
        <div class="inline-flex items-baseline rounded-full px-2.5 py-0.5 text-sm font-medium bg-green-100 text-green-800 md:mt-2 lg:mt-0">
        <svg class="-ml-1 mr-0.5 h-5 w-5 flex-shrink-0 self-center text-green-500" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
          <path fill-rule="evenodd" d="M10 17a.75.75 0 01-.75-.75V5.612L5.29 9.77a.75.75 0 01-1.08-1.04l5.25-5.5a.75.75 0 011.08 0l5.25 5.5a.75.75 0 11-1.08 1.04l-3.96-4.158V16.25A.75.75 0 0110 17z" clip-rule="evenodd" />
        </svg>
        <span class="sr-only"> Increased by </span>
        {{ $this->values['change'] }}%
      </div> <span class="text-sm">for the period</span>
      </div>
    </div>

    <div>
      <span class="text-sm font-medium text-gray-500">from {{ $this->values['previous'] }}</span>
    </div>

  </div>

</div>
