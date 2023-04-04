<div class="aura-card" wire:key="chart"  @if (!$isCached) wire:init="loadWidget" @endif>
@if($loaded)
  <div class="p-2">
    <div class="flex items-baseline justify-between mb-4">
      <span class="text-sm font-semibold">Chart {{ $widget['name'] }}</span>

    </div>

    <div class="-mb-6">
      <div class="flex items-baseline text-4xl font-medium">
        {{ collect($this->values['current'])->last() ?? 'N/A' }}
      </div>
    </div>

    <div class="-mx-6 -mb-6">
      <div class="w-full">

          <div
          style="height: 80px;"
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
                    chart: {
                      type: 'area',
                      toolbar: false,
                      width: '100%',
                      height: 80,

                      sparkline: {
                        enabled: true,
                      },

                      chart: {
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
                      }

                    },



                    colors: [getCssVariableValue('--primary-400'), getCssVariableValue('--primary-200')],

                    stroke: {
                      curve: 'smooth',
                      width: 2,
                    },
                    fill: {
                        type: 'gradient',
                        gradient: {
                            shadeIntensity: 1,
                            gradientToColors: [getCssVariableValue('--primary-400'), getCssVariableValue('--primary-200')],
                            opacityFrom: 0.7,
                            opacityTo: 0,
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
                        shadeIntensity: 0.65
                      },
                    },

                    dataLabels: {
                      enabled: false,
                    },
                    tooltip: {
                      marker: false,
                    },
                    xaxis: {
                      categories: this.labels,
                      labels: {
                        show: false,
                      },
                      axisBorder: {
                        show: false,
                      },
                      axisTicks: {
                        show: false,
                      },

                    },
                    yaxis: {
                      labels: {
                        show: false,
                      },
                    },
                    grid: {
                      show: false,
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
  @else
  <div class="p-2 animate-pulse">
    <div class="flex items-baseline justify-between mb-4">
        <div class="w-32 h-4 bg-gray-200 rounded"></div>
        <div class="w-8 h-4 bg-gray-200 rounded"></div>
    </div>

    <div class="-mx-6 -mb-6">
   <svg viewBox="0 0 300 80" preserveAspectRatio="none" class="h-16 w-full">
  <defs>
    <linearGradient id="grad" x1="0%" y1="0%" x2="0%" y2="100%">
      <stop offset="0%" style="stop-color:#e2e8f0;stop-opacity:1" />
      <stop offset="30%" style="stop-color:#e2e8f0;stop-opacity:0.5" />
      <stop offset="70%" style="stop-color:#e2e8f0;stop-opacity:0" />
    </linearGradient>
  </defs>
  <g fill="none" stroke="#e2e8f0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
    <path d="M0 70 Q5 80, 10 60 Q15 65, 20 60 Q25 70, 30 55 Q35 60, 40 50 Q45 70, 50 65 Q55 62, 60 70 Q65 50, 70 65 Q75 60, 80 48 Q85 70, 90 40 Q95 45, 100 50 Q105 60, 110 40 Q115 50, 120 45 Q125 60, 130 55 Q135 65, 140 55 Q145 70, 150 50 Q155 60, 160 55 Q165 70, 170 60 Q175 65, 180 45 Q185 50, 190 45 Q195 35, 200 30 Q205 40, 210 35 Q215 45, 220 40 Q225 50, 230 52 Q235 60, 240 48 Q245 55, 250 40 Q255 65, 260 62 Q265 70, 270 65 Q275 72, 280 55 Q285 65, 290 55 Q295 60, 300 50" fill="url(#grad)" />
  </g>
</svg>




    </div>
</div>
  @endif

 

</div>
