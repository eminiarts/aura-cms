<div class="overflow-hidden h-full aura-card" wire:key="sparkline-bar-{{ $widget['slug'] ?? '' }}" @if (!$isCached) wire:init="loadWidget" @endif>
    @if($loaded)
    <div class="flex flex-col justify-between h-full">
        <div class="flex gap-2 justify-between items-center">
            <span class="text-sm font-medium text-gray-500 truncate dark:text-gray-400">{{ __($widget['name']) }}</span>
            <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ array_sum($this->values['current']) }}</span>
        </div>

        <div class="mt-4 -mx-5 -mb-5">
            <div class="w-full">
                <div style="height: 80px;" x-data="{
              values: {{ json_encode(array_values($this->values['current'])) }},
              labels: {{ json_encode(array_keys($this->values['current'])) }},

              init() {
                  let chart = new window.ApexCharts(this.$refs.chart, this.options)

                  chart.render()

                  this.$watch('values', () => {
                      chart.updateOptions(this.options)
                  })

                  window.dispatchEvent(new Event('resize'));
              },
              get options() {
                  return {
                    chart: {
                      type: 'bar',
                      width: '100%',
                      height: 80,
                      sparkline: {
                        enabled: true
                      }
                    },

                    colors: [getCssVariableValue('--primary-400'), getCssVariableValue('--primary-200')],

                    plotOptions: {
                      bar: {
                        columnWidth: '60%',
                        borderRadius: 2,
                      }
                    },

                    xaxis: {
                      categories: this.labels,
                    },

                    series: [{
                      name: 'Current',
                      data: this.values,
                    }, {
                      name: 'Previous',
                      data: {{ json_encode(array_values($this->values['previous'])) }},
                    }],
                  }
                }
              }" class="px-2 w-full">
                    <div x-ref="chart"></div>
                </div>
            </div>
        </div>
    </div>
    @else
    <div class="animate-pulse">
        <div class="flex justify-between items-center">
            <div class="w-1/3 h-4 bg-gray-100 rounded dark:bg-gray-700"></div>
            <div class="w-10 h-4 bg-gray-100 rounded dark:bg-gray-700"></div>
        </div>
        <div class="mt-6 -mx-5 -mb-5">
            <svg viewBox="0 0 300 80" preserveAspectRatio="none" class="w-full h-16 text-gray-100 dark:text-gray-700">
                <g fill="none" fill-rule="evenodd">
                    <rect x="0" y="0" width="10" height="80" fill="currentColor"/>
                    <rect x="15" y="20" width="10" height="60" fill="currentColor"/>
                    <rect x="30" y="30" width="10" height="50" fill="currentColor"/>
                    <rect x="45" y="40" width="10" height="40" fill="currentColor"/>
                    <rect x="60" y="10" width="10" height="70" fill="currentColor"/>
                    <rect x="75" y="50" width="10" height="30" fill="currentColor"/>
                    <rect x="90" y="60" width="10" height="20" fill="currentColor"/>
                    <rect x="105" y="30" width="10" height="50" fill="currentColor"/>
                    <rect x="120" y="10" width="10" height="70" fill="currentColor"/>
                    <rect x="135" y="20" width="10" height="60" fill="currentColor"/>
                    <rect x="150" y="40" width="10" height="40" fill="currentColor"/>
                    <rect x="165" y="50" width="10" height="30" fill="currentColor"/>
                    <rect x="180" y="60" width="10" height="20" fill="currentColor"/>
                    <rect x="195" y="40" width="10" height="40" fill="currentColor"/>
                    <rect x="210" y="30" width="10" height="50" fill="currentColor"/>
                    <rect x="225" y="20" width="10" height="60" fill="currentColor"/>
                    <rect x="240" y="40" width="10" height="40" fill="currentColor"/>
                    <rect x="255" y="60" width="10" height="20" fill="currentColor"/>
                    <rect x="270" y="30" width="10" height="50" fill="currentColor"/>
                    <rect x="285" y="10" width="10" height="70" fill="currentColor"/>
                </g>
            </svg>
        </div>
    </div>
    @endif
</div>
