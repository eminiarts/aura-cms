<div class="aura-card" wire:key="chart" @if (!$isCached) wire:init="loadWidget" @endif>
    @if($loaded)
    <div class="p-2">
        <div class="flex items-start justify-between mb-4">
            <span class="text-sm font-semibold">{{ $widget['name'] }}</span>

            <div class="">
                <div class="flex items-baseline text-4xl font-medium">
                    {{ collect($this->values['current'])->last() ?? 'N/A' }}
                </div>
            </div>
        </div>

        <div class="w-full">
            <div x-data="{
              values: {{ json_encode(array_values($this->values['current'])) }},
              labels: {{ json_encode(array_keys($this->values['current'])) }},

              init() {
                  let chart = new ApexCharts(this.$refs.chart, this.options)
                  chart.render()
                  this.$watch('values', () => {
                      chart.updateOptions(this.options)
                  })

                  window.dispatchEvent(new Event('resize'));
              },
              get options() {
                  return {
                    chart: {
                      type: 'pie',
                      toolbar: false,
                      width: '100%',
                      height: '300',
                      sparkline: {
                        enabled: true,
                      },
                      animations: {
                          enabled: true,
                          easing: 'easeinout',
                          speed: 1200,
                          animateGradually: {
                              enabled: true,
                              delay: 150
                          },
                          dynamicAnimation: {
                              enabled: true,
                              speed: 350
                          }
                      }
                    },

                    colors: [getCssVariableValue('--primary-400'), getCssVariableValue('--primary-200'), getCssVariableValue('--primary-600'), getCssVariableValue('--primary-800')],

                    dataLabels: {
                      enabled: true,
                      formatter: function(val) {
                          return parseFloat(val).toFixed(0) + '%';
                      }
                    },

                    tooltip: {
                      enabled: true,
                      y: {
                        formatter: function(val) {
                          return parseFloat(val).toFixed(2) + '%';
                        }
                      }
                    },

                    legend: {
                      show: true,
                      position: 'bottom'
                    },

                    labels: this.labels,

                    series: this.values,
                  }
                }
              }" class="w-full">
                <div x-ref="chart" class="bg-white rounded-lg dark:bg-gray-800"></div>
            </div>
        </div>
    </div>
    @else
    <div class="p-2 animate-pulse">
        <div class="flex items-baseline justify-between mb-4">
            <div class="w-32 h-4 bg-gray-200 rounded"></div>
            <div class="w-8 h-4 bg-gray-200 rounded"></div>
        </div>

        <svg viewBox="0 0 36 36" class="w-64 h-64 rounded-full bg-gray-200 text-gray-200 mx-auto">
            <circle class="donut-hole" cx="18" cy="18" r="15.91549430918954" fill="#fff"></circle>
            <circle class="donut-ring" cx="18" cy="18" r="15.91549430918954" fill="transparent" stroke="currentColor"
                stroke-width="3"></circle>
        </svg>
    </div>
    @endif
</div>
