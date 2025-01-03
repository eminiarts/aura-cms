<div class="aura-card" wire:key="sparkline-bar-{{ $widget['slug'] }}" @if (!$isCached) wire:init="loadWidget" @endif>
  <div>
    @if($loaded)
  <div class="p-2">
    <div class="flex justify-between items-start mb-4">
      <span class="text-sm font-semibold">{{ $widget['name'] }}</span>

      {{-- <div class="flex items-baseline text-4xl font-medium">
        {{ collect($this->values['current'])->last() ?? 'N/A' }}
      </div> --}}
    </div>


    <div class="">
      <div class="w-full">
          <style >
            .apexcharts-tooltip-text,
.apexcharts-tooltip-series,
.apexcharts-tooltip-y-group,
.apexcharts-tooltip-z-group,
.apexcharts-tooltip-c,
.apexcharts-tooltip.active {
  padding: 0 !important;
  margin: 0 !important;
}

.apexcharts-tooltip-marker, .apexcharts-legend-marker {
  padding: 0px 0px 0px 0px !important;
  margin-right: 8px !important;
  border-radius: 4px !important;
  display: inline-block;
}

            /* ApexCharts Tooltip Container */
.apexcharts-tooltip {
  background-color: rgba(255, 255, 255, 0.9) !important; /* Change the tooltip background color */
  border-radius: 8px !important; /* Add border-radius to the tooltip */
  padding: 12px !important; /* Adjust the tooltip padding */
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1) !important; /* Add a subtle shadow */
  font-family: 'Inter', system-ui, sans-serif !important;
}

/* ApexCharts Tooltip Title (X-axis value) */
.apexcharts-tooltip-title {
  font-size: 13px !important; /* Change the font size of the tooltip title */
  line-height: 13px !important;
  font-weight: 600 !important; /* Set the font weight for the tooltip title */
  background: transparent !important;

  padding: 0px 0px 8px 0px !important;

  border-bottom: 1px solid rgb(var(--gray-400) / 0.3) !important;

  color: rgb(var(--gray-800)); /* Change the tooltip title color */
  margin-bottom: 0px !important; /* Adjust the space between the title and content */
  font-family: 'Inter', system-ui, sans-serif !important;
}
.apexcharts-text {
  font-family: 'Inter', system-ui, sans-serif !important;
  font-size: 12px;
}
.apexcharts-tooltip-text {
  font-family: 'Inter', system-ui, sans-serif !important;
  padding: 0px !important;
  font-size: 13px !important;
  line-height: 13px !important;
  color: rgb(var(--gray-600));
}

.apexcharts-tooltip-series-group {
  padding: 8px 0px 0px 0px !important;
}

/* ApexCharts Tooltip Series Marker */
.apexcharts-tooltip-series .apexcharts-tooltip-marker {

}
          </style>
          <div
          x-data="{
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
                      height: 300,
                      toolbar: {
                        show: false,
                      },

                    },

                    colors: [getCssVariableValue('--primary-400'), getCssVariableValue('--primary-200')],

                    plotOptions: {
                      bar: {
                        columnWidth: '80%',
                      }
                    },

                    xaxis: {
                      categories: this.labels,
                      tickAmount: 4,
                      labels: {
                        rotate: 0 // Set rotation to 0 degrees
                      }
                    },

                    series: [{
                      name: 'Current',
                      data: this.values,
                    },
                    @if (isset($widget['previous']))
                    {
                      name: 'Previous',
                      data: {{ json_encode(array_values($this->values['previous'])) }},
                    }
                    @endif
                    ],
                  }
                }
              }"
              class="px-0 -mb-4 w-full"
              >
              <div x-ref="chart" class="bg-white rounded-lg dark:bg-gray-800"></div>
          </div>




        {{-- {{ $this->values['current'] ?? 'N/A' }} --}}
      </div>

      {{-- <div>
        <div class="inline-flex items-baseline px-2.5 py-0.5 text-sm font-medium text-green-800 bg-green-100 rounded-full md:mt-2 lg:mt-0">
        <svg class="flex-shrink-0 self-center mr-0.5 -ml-1 w-5 h-5 text-green-500" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
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
    <div class="flex justify-between items-baseline mt-2 mb-6">
        <div class="w-32 h-4 bg-gray-200 rounded dark:bg-gray-700"></div>
        <div class="w-8 h-4 bg-gray-200 rounded dark:bg-gray-700"></div>
    </div>

    <div class="-mx-6 -mb-6">
      <svg viewBox="0 0 300 80" preserveAspectRatio="none" class="w-full h-16 text-gray-200 dark:text-gray-600">
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




</div>
