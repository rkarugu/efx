<div class="row">
    <div class="col-sm-6">
        <div id="gauge-chart"></div>
    </div>
    <div class="col-sm-6">
        <div id="bar-chart"></div>
    </div>
</div>
@push('scripts')
    <script>
        $(function() {
            var options = {
                chart: {
                    height: 200,
                    type: 'radialBar',
                },
                series: [{{ $mostDaysPayable->days ?? 0 }}], // This represents the value (e.g., 50 days)
                plotOptions: {
                    radialBar: {
                        startAngle: -135,
                        endAngle: 135,
                        dataLabels: {
                            name: {
                                fontSize: '12px',
                                offsetY: 120,
                                text: 'Days Payable Outstanding',
                            },
                            value: {
                                offsetY: 76,
                                fontSize: '22px',
                                formatter: function(val) {
                                    return val + " Days";
                                }
                            }
                        }
                    }
                },
                fill: {
                    type: 'gradient',
                    gradient: {
                        shade: 'dark',
                        type: 'horizontal',
                        gradientToColors: ['#FFB6C1'], // Gradient fill (from dark to lighter color)
                        stops: [0, 100]
                    }
                },
                stroke: {
                    lineCap: 'round'
                },
                labels: ['Longest Payable Outstanding - {{ $mostDaysPayable->invoice_no ?? 'N/A' }}']
            };

            var chart = new ApexCharts(document.querySelector("#gauge-chart"), options);
            chart.render();

        })
    </script>
    <script>
        $(function() {
            var options = {
                chart: {
                    type: 'bar',
                    height: 300,
                    events: {
                        dataPointSelection: function(event, chartContext, config) {
                            // Retrieve the index of the clicked column
                            var dataIndex = config.dataPointIndex;

                            // Map the clicked index to the aging period
                            var periods = ['< 30 Days', '< 60 Days', '< 90 Days', '< 120 Days',
                                '120+ Days'
                            ];
                            var selectedPeriod = periods[dataIndex];

                            // Redirect or trigger an action based on the selected period
                            if (selectedPeriod === '< 30 Days') {
                                window.open(
                                    '{{ route('maintain-suppliers.processed_invoices.index') }}?period=under30',
                                    '_blank');
                            } else if (selectedPeriod === '< 60 Days') {
                                window.open(
                                    '{{ route('maintain-suppliers.processed_invoices.index') }}?period=under60',
                                    '_blank');
                            } else if (selectedPeriod === '< 90 Days') {
                                window.open(
                                    '{{ route('maintain-suppliers.processed_invoices.index') }}?period=under90',
                                    '_blank');
                            } else if (selectedPeriod === '< 120 Days') {
                                window.open(
                                    '{{ route('maintain-suppliers.processed_invoices.index') }}?period=under120',
                                    '_blank');
                            } else if (selectedPeriod === '120+ Days') {
                                window.open(
                                    '{{ route('maintain-suppliers.processed_invoices.index') }}?period=over120',
                                    '_blank');
                            }
                        }
                    }
                },
                series: [{
                    name: 'Payable Amount',
                    data: {!! $aging !!} // The amounts corresponding to each age category
                }],
                xaxis: {
                    categories: ['< 30 Days', '< 60 Days', '< 90 Days', '< 120 Days', '120+ Days']
                },
                title: {
                    text: 'Payable Aging',
                    align: 'center'
                },
                plotOptions: {
                    bar: {
                        horizontal: false,
                        columnWidth: '50%',
                    }
                },
                yaxis: {
                    title: {
                        text: 'Amount'
                    }
                },
                labels: {
                    total: 'Total Payable 500' // You can customize this based on your data
                }
            };

            var chart = new ApexCharts(document.querySelector("#bar-chart"), options);
            chart.render();
        })
    </script>
@endpush
