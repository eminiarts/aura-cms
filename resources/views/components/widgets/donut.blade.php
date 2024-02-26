<div class="aura-card" wire:key="chart" @if (!$isCached) wire:init="loadWidget" @endif>
  @if($loaded)
  <div class="p-2">
    <div class="flex justify-between items-start mb-4">
      <span class="text-sm font-semibold">{{ $widget['name'] }}</span>

      <div class="">
        {{-- <div class="flex items-baseline text-4xl font-medium">
          {{ collect($this->values['current'])->last() ?? 'N/A' }}
        </div> --}}
      </div>
    </div>

    <div class="w-full">
      <div x-data="{
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
          const isDarkMode = document.documentElement.classList.contains('dark');
          const lightModeColors = [getCssVariableValue('--primary-400'), getCssVariableValue('--primary-200'), getCssVariableValue('--primary-600'), getCssVariableValue('--primary-800')];
          const darkModeColors = [getCssVariableValue('--primary-400'), getCssVariableValue('--primary-200'), getCssVariableValue('--primary-600'), getCssVariableValue('--primary-800')];

          return {
            labels: this.labels,
            series: this.values,
            chart: {
              type: 'donut',
              width: '100%',
              height: '300',

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

            colors: isDarkMode ? darkModeColors : lightModeColors,

            stroke: {
              width: isDarkMode ? 2 : 2,
              colors: isDarkMode ? [getCssVariableValue('--gray-900')] : [getCssVariableValue('--gray-100')]
            },

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
                  // return parseFloat(val).toFixed(2) + '%';
                  return parseFloat(val).toFixed(0);
                }
              }
            },

            legend: {
              show: true,
              position: 'bottom',
              labels: {
                colors: isDarkMode ? getCssVariableValue('--gray-200') : getCssVariableValue('--gray-900'), // Replace with actual color values
              }
            }
          }
        }
      }" class="w-full">
          <div x-ref="chart" class="bg-white rounded-lg dark:bg-gray-900"></div>
      </div>
    </div>
  </div>
  @else
    <div class="p-2 animate-pulse">
      <div class="flex justify-between items-baseline mt-2 mb-6">
          <div class="w-32 h-4 bg-gray-200 rounded dark:bg-gray-700"></div>
          <div class="w-8 h-4 bg-gray-200 rounded dark:bg-gray-700"></div>
      </div>

      <svg viewBox="0 0 36 36" class="mx-auto w-64 h-64 text-gray-200 rounded-full dark:text-gray-700">
          <circle class="text-gray-500 donut-hole dark:text-gray-900" cx="18" cy="18" r="15.91549430918954" fill="currentColor"></circle>
          <circle class="donut-ring" cx="18" cy="18" r="15.91549430918954" fill="transparent" stroke="currentColor"
              stroke-width="3"></circle>
      </svg>
    </div>
  @endif
</div>
