<div class="{{ $width }} px-2 mb-4">


    <div class="aura-card">

    <div class="flex items-start justify-between ">
        <dt class="text-sm font-medium text-gray-500 truncate">{{ $this->name }}</dt>
        <div>
            <select wire:model="range" id="range" name="range" class="block w-full py-1 mt-1 text-base border-gray-500/30 rounded-md focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                @foreach($this->ranges() as $key => $range)
                <option value="{{ $key }}">{{ $range }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="px-0">
        <dd class="flex items-baseline mt-1 text-3xl font-semibold text-gray-900 dark:text-gray-100">
            <p>{{ $this->current->sum() }}</p>
        </dd>
    </div>

        {{-- @dump($this->current, $this->previous) --}}
        {{-- @dump($this->current->keys()->toArray(),$this->current->values()) --}}


        <div class="z-0" x-data="{
            current:{{ Js::from($this->current->values()->toArray()) }},
            previous:{{ Js::from($this->previous->values()->toArray()) }},
            labels:{{ Js::from($this->current->keys()->toArray()) }},


            init() {
                let chart = new ApexCharts(this.$refs.chart, this.options)

                chart.render()

                this.$watch('current', () => {
                    chart.updateOptions(this.options)
                })
            },
            get options() {
                return {
                    chart: { type: 'line', toolbar: false, height: 150, },
                    fill: {
                        type: 'gradient',
                        gradient: {
                            shadeIntensity: 1,
                            opacityFrom: 0.7,
                            opacityTo: 0.9,
                            stops: [0, 90, 100]
                        }
                    },
                    tooltip: {
                        marker: false,
                        y: {
                            formatter(number) {
                                return number
                            }
                        }
                    },
                    stroke: {
                        width: 3,
                        curve: 'smooth'
                    },
                    yaxis:{
                        show: false,

                    },
                    xaxis: {
                        show: false,
                        type: 'datetime',
                        categories: this.labels,
                        labels: {
                            show:false
                        },
                        axisBorder: { show: false },
                        axisTicks: { show:false},
                    },
                    colors: [getCssVariableValue('--primary-400'), getCssVariableValue('--primary-600')],

                    legend: {   show: true},
                    series: [{
                        name: 'Current',
                        data: this.current,
                    }, {
                        name: 'Previous',
                        data: this.previous,
                    }],
                    grid: {
                        show: false,      // you can either change hear to disable all grids

                        {{-- yaxis: {
                            lines: {
                                show: true  //or just here to disable only y axis
                            }
                        },    --}}
                    },
                    legend: {
                        position: 'top',
                        horizontalAlign: 'right',
                        floating: true,
                        {{-- offsetY: 0 --}}
                    },
                    fill: {
                        type: 'gradient',
                        colors: ['#f00', '#ccc'],
                        gradient: {
                            shade: 'dark',
                            gradientToColors: [getCssVariableValue('--primary-200'), getCssVariableValue('--primary-400')],
                            shadeIntensity: 1,
                            type: 'horizontal',
                            opacityFrom: 1,
                            opacityTo: 1,
                            stops: [0, 100, 100, 100]
                        },
                    },
                }
            }
        }">
        <div x-ref="chart" class=""></div>
    </div>

    </div>

     @once
        @push('scripts')
            <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
        @endpush
    @endonce

</div>
