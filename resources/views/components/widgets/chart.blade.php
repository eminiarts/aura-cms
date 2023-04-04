<div class="aura-card">
  {{-- @dump($start)
  @dump($end)
  @dump($widget)
  @dump($this->values) --}}

  <div class="p-2">
    <div class="flex items-baseline justify-between mb-4">
      <span class="text-sm font-semibold">Chart {{ $widget['name'] }}</span>

      {{-- <div class="ml-4">
        <span class="text-xs font-normal text-gray-500">{{ $start }} - {{ $end }}</span>
      </div> --}}
    </div>

    <div class="flex items-baseline justify-between mt-1 mb-2 md:block lg:flex">
      <div class="flex items-baseline text-4xl font-medium">

          <div
          x-data="{
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
                      chart: { type: 'area',
                      toolbar: false,
                      height: 500,


                  },

                  colors:[getCssVariableValue('--primary-400')],

                  stroke: {
                      curve: 'smooth',
                  },
                  fill: {
                      type: 'gradient',
                      gradient: {
                          shadeIntensity: 1,
                          gradientToColors: ['#B3B3B300'],
                          opacityFrom: 0.7,
                          opacityTo: 0.9,
                          stops: [0, 90, 100]
                      }
                  },

                  theme: {
                      mode: 'light',
                      palette: 'palette1',
                      monochrome: {
                          enabled: false,
                          color: '#255aee',
                          shadeTo: 'light  ',
                          shadeIntensity: 0.65 },
                      },

                      dataLabels: {
                          enabled: false,},
                          tooltip: {
                              marker: false,
                          },
                          xaxis: { categories: this.labels,

                              labels: {show:false,
                              },
                          },


                          yaxis: {
                              labels: {show:false,
                              },
                          },


                          grid: {
                              show: false,
                          },

                          series: [{
                              name: 'Created',
                              data: this.values,
                          }],


                      }
                  }
              }"
              class="w-full"
              >
              <div x-ref="chart" class="bg-white rounded-lg dark:bg-gray-800"></div>
          </div>




        {{-- {{ $this->values['current'] ?? 'N/A' }} --}}
      </div>

      {{-- <div>
        <div class="inline-flex items-baseline rounded-full px-2.5 py-0.5 text-sm font-medium bg-green-100 text-green-800 md:mt-2 lg:mt-0">
        <svg class="-ml-1 mr-0.5 h-5 w-5 flex-shrink-0 self-center text-green-500" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
          <path fill-rule="evenodd" d="M10 17a.75.75 0 01-.75-.75V5.612L5.29 9.77a.75.75 0 01-1.08-1.04l5.25-5.5a.75.75 0 011.08 0l5.25 5.5a.75.75 0 11-1.08 1.04l-3.96-4.158V16.25A.75.75 0 0110 17z" clip-rule="evenodd" />
        </svg>
        <span class="sr-only"> Increased by </span>
        {{-- {{ $this->values['change'] }}%
      </div> <span class="text-sm">for the period</span>
      </div> --}}
    </div>

    {{-- <div>
      <span class="text-sm font-medium text-gray-500">from {{ $this->values['previous'] }}</span>
    </div> --}}

  </div>

  <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

  <script>
      // after 100ms trigger a window resize event to force the chart to redraw
      setTimeout(function() {
          window.dispatchEvent(new Event('resize'));
      }, 0);
      setTimeout(function() {
          window.dispatchEvent(new Event('resize'));
      }, 100);
  </script>

</div>
