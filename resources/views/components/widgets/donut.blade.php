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
                      type: 'donut',
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

                    colors: [getCssVariableValue('--primary-400'), getCssVariableValue('--primary-200')],

                    dataLabels: {
                      enabled: true,
                      formatter: function(val) {
                          return val + '%';
                      }
                    },

                    tooltip: {
                      enabled: true,
                      y: {
                        formatter: function(val) {
                          return val + '%';
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

        <div class="w-full h-64 bg-gray-200 rounded"></div>
    </div>
    @endif
</div>
