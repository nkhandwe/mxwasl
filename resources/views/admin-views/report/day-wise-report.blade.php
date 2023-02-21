@extends('layouts.admin.app')

@section('title', translate('messages.day_wise_report'))

@push('css_or_js')
@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-header-title">
                <span class="page-header-icon">
                    <img src="{{ asset('public/assets/admin/img/report.png') }}" class="w--22" alt="">
                </span>
                <span>
                    {{ translate('messages.day_wise_report') }} <span class="mb-0 h6 badge badge-soft-success ml-2"
                        id="itemCount">( {{ session('from_date') }} - {{ session('to_date') }} )</span>
                </span>
            </h1>
        </div>
        <!-- End Page Header -->

        <div class="card">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-lg-12">
                        <div class="report-card-inner mb-3 pt-3">
                            <form action="{{ route('admin.report.set-date') }}" method="post">
                                @csrf
                                <div class="d-flex flex-wrap justify-content-between align-items-center mb-2">
                                    <h5 class="form-label m-0 mb-2 mr-2">
                                        {{ translate('messages.show') }} {{ translate('messages.data') }} by
                                        {{ translate('messages.date') }}
                                        {{ translate('messages.range') }}
                                    </h5>
                                    <button type="submit"
                                        class="btn btn--primary mb-2">{{ translate('show_data') }}</button>
                                </div>
                                <div class="row g-3">
                                    <div class="col-sm-6">
                                        <select name="module_id" class="form-control js-select2-custom"
                                            onchange="set_filter('{{ url()->full() }}',this.value,'module_id')"
                                            title="{{ translate('messages.select') }} {{ translate('messages.modules') }}">
                                            <option value="" {{ !request('module_id') ? 'selected' : '' }}>
                                                {{ translate('messages.all') }} {{ translate('messages.modules') }}
                                            </option>
                                            @foreach (\App\Models\Module::notParcel()->get() as $module)
                                                <option value="{{ $module->id }}"
                                                    {{ request('module_id') == $module->id ? 'selected' : '' }}>
                                                    {{ $module['module_name'] }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    @if (!isset(auth('admin')->user()->zone_id))
                                        <div class="col-sm-6">
                                            <select name="zone_id" class="form-control js-select2-custom"
                                                onchange="set_filter('{{ url()->full() }}',this.value, 'zone_id')">
                                                <option value="" {{ !isset($zone) ? 'selected' : '' }}>
                                                    {{ translate('messages.all') }} {{ translate('messages.zone') }}
                                                </option>
                                                @foreach (\App\Models\Zone::orderBy('name')->get() as $z)
                                                    <option value="{{ $z['id'] }}"
                                                        {{ isset($zone) && $zone->id == $z['id'] ? 'selected' : '' }}>
                                                        {{ $z['name'] }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    @endif
                                    <div class="col-sm-6">
                                        <input type="date" name="from" id="from_date"
                                            {{ session()->has('from_date') ? 'value=' . session('from_date') : '' }}
                                            class="form-control" required>
                                    </div>
                                    <div class="col-sm-6">
                                        <input type="date" name="to" id="to_date"
                                            {{ session()->has('to_date') ? 'value=' . session('to_date') : '' }}
                                            class="form-control" required>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    @php
                        $from = session('from_date') . ' 00:00:00';
                        $to = session('to_date') . ' 23:59:59';
                        $total = \App\Models\Order::when(isset($zone), function ($query) use ($zone) {
                            return $query->where('zone_id', $zone->id);
                        })
                            ->when(request('module_id'), function ($query) {
                                return $query->module(request('module_id'));
                            })
                            ->whereBetween('created_at', [$from, $to])
                            ->Notpos()
                            ->count();
                        if ($total == 0) {
                            $total = 0.01;
                        }
                    @endphp
                    <!--Admin earned-->
                    <div class="col-sm-6 col-xl-3">
                        @php
                            $admin_earned = $order_transactions->sum('admin_commission')+$order_transactions->sum('admin_expense')-$order_transactions->sum('delivery_fee_comission');
                            $store_earned = $order_transactions->sum('store_amount');
                            $deliveryman_earned = $order_transactions->sum('delivery_charge');
                            $total_sell = $order_transactions->sum('order_amount');
                        @endphp
                        <!-- Card -->
                        <div class="resturant-card resturant-card-2 card--bg-1">
                            <h4 class="title">{{ \App\CentralLogics\Helpers::format_currency($admin_earned) }}</h4>
                            <span class="subtitle">{{ translate('messages.admin') }}
                                {{ translate('messages.earned') }}</span>
                            <img class="resturant-icon" src="{{ asset('public/assets/admin/img/report/earned.png') }}"
                                alt="order-report">
                        </div>
                        <!-- End Card -->
                    </div>
                    <!--Admin earned End-->
                    <!--restaurant earned-->
                    <div class="col-sm-6 col-xl-3">
                        <!-- Card -->
                        <div class="resturant-card resturant-card-2 card--bg-2">
                            <h4 class="title">{{ \App\CentralLogics\Helpers::format_currency($store_earned) }}</h4>
                            <span class="subtitle">{{ translate('messages.store') }}
                                {{ translate('messages.earned') }}</span>
                            <img class="resturant-icon" src="{{ asset('public/assets/admin/img/report/res-earned.png') }}"
                                alt="order-report">
                        </div>
                        <!-- End Card -->
                    </div>
                    <!--restaurant earned end-->
                    <!--Deliveryman earned-->
                    <div class="col-sm-6 col-xl-3">
                        <!-- Card -->
                        <div class="resturant-card resturant-card-2 card--bg-3">
                            <h4 class="title">{{ \App\CentralLogics\Helpers::format_currency($deliveryman_earned) }}</h4>
                            <span class="subtitle">{{ translate('messages.delivery') }} {{ translate('messages.fee') }}
                                {{ translate('messages.earned') }}</span>
                            <img class="resturant-icon"
                                src="{{ asset('public/assets/admin/img/report/delivery-fee.png') }}" alt="order-report">
                        </div>
                        <!-- End Card -->
                    </div>
                    <!--Deliveryman earned end-->
                    <!--Total sell-->
                    <div class="col-sm-6 col-xl-3">
                        <!-- Card -->
                        <div class="resturant-card resturant-card-2 card--bg-4">
                            <h4 class="title">{{ \App\CentralLogics\Helpers::format_currency($total_sell) }}</h4>
                            <span class="subtitle">{{ translate('messages.total_sell') }}</span>
                            <img class="resturant-icon" src="{{ asset('public/assets/admin/img/report/sell.png') }}"
                                alt="order-report">
                        </div>
                        <!-- End Card -->
                    </div>
                    <!--total sell end-->

                    <!--In progress-->
                    <div class="col-sm-6 col-xl-3">
                        @php
                            $returned = \App\Models\Order::when(isset($zone), function ($query) use ($zone) {
                                return $query->where('zone_id', $zone->id);
                            })
                                ->when(request('module_id'), function ($query) {
                                    return $query->module(request('module_id'));
                                })
                                ->whereIn('order_status', ['pending', 'accepted', 'confirmed', 'processing', 'handover', 'picked_up'])
                                ->whereBetween('created_at', [$from, $to])
                                ->Notpos()
                                ->count();
                        @endphp
                        <div class="resturant-card resturant-card-2 card--bg-4">
                            <h6 class="title">
                                {{ $returned }}
                            </h6>
                            <span class="subtitle text-capitalize">
                                {{ translate('messages.in') }} {{ translate('messages.progress') }}
                            </span>
                            <img src="{{ asset('public/assets/admin/img/report/progress.png') }}" alt="dashboard"
                                class="resturant-icon">
                        </div>
                    </div>
                    <!--In progress End-->
                    <!--In Delivered -->
                    <div class="col-sm-6 col-xl-3">
                        @php
                            $delivered = \App\Models\Order::when(isset($zone), function ($query) use ($zone) {
                                return $query->where('zone_id', $zone->id);
                            })
                                ->when(request('module_id'), function ($query) {
                                    return $query->module(request('module_id'));
                                })
                                ->where(['order_status' => 'delivered'])
                                ->whereBetween('created_at', [$from, $to])
                                ->Notpos()
                                ->count();
                        @endphp
                        <div class="resturant-card resturant-card-2 card--bg-3">
                            <h6 class="title">
                                {{ $delivered }}
                            </h6>
                            <span class="subtitle text-capitalize">
                                {{ translate('messages.delivered') }}
                            </span>
                            <img src="{{ asset('public/assets/admin/img/report/delivered.png') }}" alt="dashboard"
                                class="resturant-icon">
                        </div>
                    </div>
                    <!--Delivered End-->
                    <!--Failed-->
                    <div class="col-sm-6 col-xl-3">
                        @php
                            $failed = \App\Models\Order::when(isset($zone), function ($query) use ($zone) {
                                return $query->where('zone_id', $zone->id);
                            })
                                ->when(request('module_id'), function ($query) {
                                    return $query->module(request('module_id'));
                                })
                                ->where(['order_status' => 'failed'])
                                ->whereBetween('created_at', [$from, $to])
                                ->Notpos()
                                ->count();
                        @endphp
                        <div class="resturant-card resturant-card-2 card--bg-2">
                            <h6 class="title">
                                {{ $failed }}
                            </h6>
                            <span class="subtitle text-capitalize">
                                {{ translate('messages.failed') }}
                            </span>
                            <img src="{{ asset('public/assets/admin/img/report/failed.png') }}" alt="dashboard"
                                class="resturant-icon">
                        </div>
                    </div>
                    <!--Failed End-->
                    <!--Canceled-->
                    <div class="col-sm-6 col-xl-3">
                        @php
                            $canceled = \App\Models\Order::when(isset($zone), function ($query) use ($zone) {
                                return $query->where('zone_id', $zone->id);
                            })
                                ->when(request('module_id'), function ($query) {
                                    return $query->module(request('module_id'));
                                })
                                ->where(['order_status' => 'canceled'])
                                ->whereBetween('created_at', [$from, $to])
                                ->Notpos()
                                ->count();
                        @endphp
                        <div class="resturant-card resturant-card-2 card--bg-1">
                            <h6 class="title">
                                {{ $canceled }}
                            </h6>
                            <span class="subtitle text-capitalize">
                                {{ translate('messages.canceled') }}
                            </span>
                            <img src="{{ asset('public/assets/admin/img/report/canceled.png') }}" alt="dashboard"
                                class="resturant-icon">
                        </div>
                    </div>
                    <!--canceled End-->

                </div>
            </div>
        </div>

        <!-- End Stats -->
        <!-- Card -->
        <div class="card mt-3">
            <!-- Header -->
            <div class="card-header border-0 py-2">
                <div class="search--button-wrapper">
                    <h3 class="card-title">
                        {{ translate('messages.order') }} {{ translate('messages.transactions') }} <span
                            class="badge badge-soft-secondary" id="countItems">{{ $order_transactions->total() }}</span>
                    </h3>
                    <form action="javascript:" id="search-form" class="search-form">
                        <!-- Search -->
                        <div class="input--group input-group input-group-merge input-group-flush">
                            <input class="form-control" placeholder="{{ translate('Search by Order ID') }}">
                            <button type="submit" class="btn btn--secondary"><i class="tio-search"></i></button>
                        </div>
                        <!-- End Search -->
                    </form>
                    <!-- Static Export Button -->
                    <div class="hs-unfold ml-3">
                        <a class="js-hs-unfold-invoker btn btn-sm btn-white dropdown-toggle btn export-btn btn-outline-primary btn--primary font--sm"
                            href="javascript:;"
                            data-hs-unfold-options="{
                                &quot;target&quot;: &quot;#usersExportDropdown&quot;,
                                &quot;type&quot;: &quot;css-animation&quot;
                            }"
                            data-hs-unfold-target="#usersExportDropdown" data-hs-unfold-invoker="">
                            <i class="tio-download-to mr-1"></i> {{ translate('export') }}
                        </a>

                        <div id="usersExportDropdown"
                            class="hs-unfold-content dropdown-unfold dropdown-menu dropdown-menu-sm-right hs-unfold-content-initialized hs-unfold-css-animation animated hs-unfold-reverse-y hs-unfold-hidden">

                            <span class="dropdown-header">{{ translate('download_options') }}</span>
                            <a id="export-excel" class="dropdown-item"
                                href="{{ route('admin.report.day-wise-report-export', ['type' => 'excel', request()->getQueryString()]) }}">
                                <img class="avatar avatar-xss avatar-4by3 mr-2"
                                    src="{{ asset('public/assets/admin/svg/components/excel.svg') }}"
                                    alt="Image Description">
                                {{ translate('messages.excel') }}
                            </a>
                            <a id="export-csv" class="dropdown-item"
                                href="{{ route('admin.report.day-wise-report-export', ['type' => 'csv', request()->getQueryString()]) }}">
                                <img class="avatar avatar-xss avatar-4by3 mr-2"
                                    src="{{ asset('public/assets/admin/svg/components/placeholder-csv-format.svg') }}"
                                    alt="Image Description">
                                .{{ translate('messages.csv') }}
                            </a>

                        </div>
                    </div>
                    <!-- Static Export Button -->
                </div>
            </div>
            <!-- End Header -->

            <!-- Body -->
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table id="datatable" class="table table-thead-bordered table-align-middle card-table">
                        <thead class="thead-light">
                            <tr>
                                <th class="border-0">{{ translate('sl') }}</th>
                                <th class="border-0">{{ translate('messages.order') }} {{ translate('messages.id') }}
                                </th>
                                <th class="border-0">{{ translate('messages.total_order_amount') }}</th>
                                <th class="border-0">{{ translate('messages.store_commission') }}</th>
                                <th class="border-0">{{ translate('messages.admin_commission') }}</th>
                                <th class="border-0">{{ translate('messages.delivery') }} {{ translate('messages.fee') }}
                                </th>
                                <th>{{ translate('Comission on delivery fee') }}</th>
                                <th class="border-0">{{ translate('messages.vat/tax') }}</th>
                                <th class="border-0">{{ translate('messages.amount') }}
                                    {{ translate('messages.received_by') }}</th>
                                <th class="border-0">{{ translate('messages.created_at') }}</th>
                            </tr>
                        </thead>
                        <tbody id="set-rows">
                            @foreach ($order_transactions as $k => $ot)
                                <tr scope="row">
                                    <td>{{ $k + $order_transactions->firstItem() }}</td>
                                    <td><a href="{{route('admin.order.details',$ot->order_id)}}">{{$ot->order_id}}</a></td>
                                    <td>{{\App\CentralLogics\Helpers::format_currency($ot->order_amount)}}</td>
                                    <td>{{\App\CentralLogics\Helpers::format_currency($ot->store_amount - $ot->tax)}}</td>
                                    <td>{{\App\CentralLogics\Helpers::format_currency($ot->admin_commission+$ot->admin_expense-$ot->delivery_fee_comission)}}</td>
                                    <td>{{\App\CentralLogics\Helpers::format_currency($ot->delivery_charge)}}</td>
                                    <td>{{\App\CentralLogics\Helpers::format_currency($ot->delivery_fee_comission)}}</td>
                                    <td>{{\App\CentralLogics\Helpers::format_currency($ot->tax)}}</td>
                                    @if ($ot->received_by == 'admin')
                                        <td class="text-capitalize">{{ translate('messages.admin') }}</td>
                                    @elseif ($ot->received_by == 'deliveryman')
                                        <td class="text-capitalize">{{ translate('messages.delivery_man') }}</td>
                                    @elseif ($ot->received_by == 'store')
                                        <td class="text-capitalize">{{ translate('messages.store') }}</td>
                                    @endif
                                    <td>{{ $ot->created_at->format('Y/m/d ' . config('timeformat')) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <!-- End Body -->
            @if (count($order_transactions) !== 0)
                <hr>
            @endif
            <div class="page-area">
                {!! $order_transactions->links() !!}
            </div>
            @if (count($order_transactions) === 0)
                <div class="empty--data">
                    <img src="{{ asset('/public/assets/admin/svg/illustrations/sorry.svg') }}" alt="public">
                    <h5>
                        {{ translate('no_data_found') }}
                    </h5>
                </div>
            @endif
        </div>
        <!-- End Card -->
    </div>
@endsection

@push('script')
@endpush

@push('script_2')
    <script src="{{ asset('public/assets/admin') }}/vendor/chart.js/dist/Chart.min.js"></script>
    <script src="{{ asset('public/assets/admin') }}/vendor/chartjs-chart-matrix/dist/chartjs-chart-matrix.min.js"></script>
    <script src="{{ asset('public/assets/admin') }}/js/hs.chartjs-matrix.js"></script>

    <script>
        $(document).on('ready', function() {

            // INITIALIZATION OF FLATPICKR
            // =======================================================
            $('.js-flatpickr').each(function() {
                $.HSCore.components.HSFlatpickr.init($(this));
            });


            // INITIALIZATION OF NAV SCROLLER
            // =======================================================
            $('.js-nav-scroller').each(function() {
                new HsNavScroller($(this)).init()
            });


            // INITIALIZATION OF DATERANGEPICKER
            // =======================================================
            $('.js-daterangepicker').daterangepicker();

            $('.js-daterangepicker-times').daterangepicker({
                timePicker: true,
                startDate: moment().startOf('hour'),
                endDate: moment().startOf('hour').add(32, 'hour'),
                locale: {
                    format: 'M/DD hh:mm A'
                }
            });

            var start = moment();
            var end = moment();

            function cb(start, end) {
                $('#js-daterangepicker-predefined .js-daterangepicker-predefined-preview').html(start.format(
                    'MMM D') + ' - ' + end.format('MMM D, YYYY'));
            }

            $('#js-daterangepicker-predefined').daterangepicker({
                startDate: start,
                endDate: end,
                ranges: {
                    'Today': [moment(), moment()],
                    'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                    'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                    'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                    'This Month': [moment().startOf('month'), moment().endOf('month')],
                    'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1,
                        'month').endOf('month')]
                }
            }, cb);

            cb(start, end);


            // INITIALIZATION OF CHARTJS
            // =======================================================
            $('.js-chart').each(function() {
                $.HSCore.components.HSChartJS.init($(this));
            });

            var updatingChart = $.HSCore.components.HSChartJS.init($('#updatingData'));

            // Call when tab is clicked
            $('[data-toggle="chart"]').click(function(e) {
                let keyDataset = $(e.currentTarget).attr('data-datasets')

                // Update datasets for chart
                updatingChart.data.datasets.forEach(function(dataset, key) {
                    dataset.data = updatingChartDatasets[keyDataset][key];
                });
                updatingChart.update();
            })


            // INITIALIZATION OF MATRIX CHARTJS WITH CHARTJS MATRIX PLUGIN
            // =======================================================
            function generateHoursData() {
                var data = [];
                var dt = moment().subtract(365, 'days').startOf('day');
                var end = moment().startOf('day');
                while (dt <= end) {
                    data.push({
                        x: dt.format('YYYY-MM-DD'),
                        y: dt.format('e'),
                        d: dt.format('YYYY-MM-DD'),
                        v: Math.random() * 24
                    });
                    dt = dt.add(1, 'day');
                }
                return data;
            }

            $.HSCore.components.HSChartMatrixJS.init($('.js-chart-matrix'), {
                data: {
                    datasets: [{
                        label: 'Commits',
                        data: generateHoursData(),
                        width: function(ctx) {
                            var a = ctx.chart.chartArea;
                            return (a.right - a.left) / 70;
                        },
                        height: function(ctx) {
                            var a = ctx.chart.chartArea;
                            return (a.bottom - a.top) / 10;
                        }
                    }]
                },
                options: {
                    tooltips: {
                        callbacks: {
                            title: function() {
                                return '';
                            },
                            label: function(item, data) {
                                var v = data.datasets[item.datasetIndex].data[item.index];

                                if (v.v.toFixed() > 0) {
                                    return '<span class="font-weight-bold">' + v.v.toFixed() +
                                        ' hours</span> on ' + v.d;
                                } else {
                                    return '<span class="font-weight-bold">No time</span> on ' + v.d;
                                }
                            }
                        }
                    },
                    scales: {
                        xAxes: [{
                            position: 'bottom',
                            type: 'time',
                            offset: true,
                            time: {
                                unit: 'week',
                                round: 'week',
                                displayFormats: {
                                    week: 'MMM'
                                }
                            },
                            ticks: {
                                "labelOffset": 20,
                                "maxRotation": 0,
                                "minRotation": 0,
                                "fontSize": 12,
                                "fontColor": "rgba(22, 52, 90, 0.5)",
                                "maxTicksLimit": 12,
                            },
                            gridLines: {
                                display: false
                            }
                        }],
                        yAxes: [{
                            type: 'time',
                            offset: true,
                            time: {
                                unit: 'day',
                                parser: 'e',
                                displayFormats: {
                                    day: 'ddd'
                                }
                            },
                            ticks: {
                                "fontSize": 12,
                                "fontColor": "rgba(22, 52, 90, 0.5)",
                                "maxTicksLimit": 2,
                            },
                            gridLines: {
                                display: false
                            }
                        }]
                    }
                }
            });


            // INITIALIZATION OF CLIPBOARD
            // =======================================================
            $('.js-clipboard').each(function() {
                var clipboard = $.HSCore.components.HSClipboard.init(this);
            });


            // INITIALIZATION OF CIRCLES
            // =======================================================
            $('.js-circle').each(function() {
                var circle = $.HSCore.components.HSCircles.init($(this));
            });
        });
    </script>

    <script>
        $('#from_date,#to_date').change(function() {
            let fr = $('#from_date').val();
            let to = $('#to_date').val();
            if (fr != '' && to != '') {
                if (fr > to) {
                    $('#from_date').val('');
                    $('#to_date').val('');
                    toastr.error('Invalid date range!', Error, {
                        CloseButton: true,
                        ProgressBar: true
                    });
                }
            }

        })

        $('#search-form').on('submit', function(e) {
            e.preventDefault();
            var formData = new FormData(this);
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            $.post({
                url: '{{ route('admin.report.day-wise-report-search') }}',
                data: formData,
                cache: false,
                contentType: false,
                processData: false,
                beforeSend: function() {
                    $('#loading').show();
                },
                success: function(data) {
                    $('#set-rows').html(data.view);
                    $('#countItems').html(data.count);
                    $('.page-area').hide();
                },
                complete: function() {
                    $('#loading').hide();
                },
            });
        });
    </script>
@endpush
