@extends('layouts.admin.app')

@section('title', translate('mis_report'))

@push('css_or_js')
@endpush

@section('content')
<div class="content container-fluid">
    <!-- End Page Header -->
    <div class="card mb-2">
        <div class="card-header text-capitalize py-0">
            <h4 class="pt-1">{{ translate('messages.mis_report') }}</h4>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-lg-12 pt-3">
                    <form action="{{ route('admin.report.order.mis') }}" method="get">
                        <div class="row">
                            <div class="col-sm-6 col-12">
                                <div class="mb-3">
                                    <label for="">{{ translate('messages.from') }}</label>
                                    <input type="date" name="from" id="from_date" value="{{ request()->get('from') }}"
                                        class="form-control"
                                        title="{{ translate('messages.from') }} {{ translate('messages.date') }}">
                                </div>
                            </div>
                            <div class="col-sm-6 col-12">
                                <div class="mb-3">
                                    <label for="">{{ translate('messages.to') }}</label>
                                    <input type="date" name="to" id="to_date" value="{{ request()->get('to') }}"
                                        class="form-control"
                                        title="{{ ucfirst(translate('messages.to')) }} {{ translate('messages.date') }}">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <button type="submit"
                                class="btn btn-primary col-lg-2 col-md-3 col-sm-4 col-6 mx-3 text-capitalize font-weight-bold"><i
                                    class="tio-filter-list mr-1"></i>{{ translate('messages.filter') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>
    <!-- Card -->
    <div class="card">
        <!-- Header -->
        <div class="card-header">
            <div class="row justify-content-between align-items-center flex-grow-1">
                <div class="offset-lg-8 col-lg-4">
                    <div class="d-sm-flex justify-content-sm-end">
                        <!-- Datatable Info -->
                        <div id="datatableCounterInfo" class="mr-2 mb-2 mb-sm-0" style="display: none;">
                            <div class="d-flex align-items-center">
                                <span class="font-size-sm mr-3">
                                    <span id="datatableCounter">0</span>
                                    {{ translate('messages.selected') }}
                                </span>

                            </div>
                        </div>
                        <!-- End Datatable Info -->
                        <!-- Unfold -->
                        <div class="hs-unfold mr-2">
                            <a class="js-hs-unfold-invoker btn btn-sm btn-white"
                                href="{{ route('admin.report.order.mis.export') }}">
                                <i class="tio-download-to mr-1"></i> {{ translate('messages.export') }}
                            </a>
                        </div>
                        <!-- End Unfold -->
                    </div>
                </div>
            </div>
            <!-- End Row -->
        </div>
        <!-- End Header -->
        <!-- Table -->
        <div class="table-responsive datatable-custom">
            <table id="datatable" class="table table-thead-bordered table-align-middle card-table table-nowrap"
                style="width: 100%">
                <thead class="thead-light">
                    <tr>
                        <th>{{ translate('messages.#') }}</th>
                        <th>{{ translate('messages.date') }}</th>
                        <th>{{ translate('messages.order_id') }}</th>
                        <th>{{ translate('messages.vendor_id') }}</th>
                        <th>{{ translate('messages.store_name') }}</th>
                        <th>{{ translate('messages.store_id') }}</th>
                        <th>{{ translate('messages.payment_mode') }}</th>
                        <th>{{ translate('messages.vendor_price') }}</th>
                        <th>{{ translate('messages.item_price') }}</th>
                        <th>{{ translate('messages.delivery_fees') }}</th>
                        <th>{{ translate('Tax (with GST)') }}</th>
                        <th>{{ translate('messages.total') }}</th>
                        <th>{{ translate('messages.coupon_code') }}</th>
                        <th>{{ translate('messages.promo_amount') }}</th>
                        <th>{{ translate('messages.admin_commission') }}</th>
                        <th>{{ translate('messages.customer_id') }}</th>
                        <th>{{ translate('messages.customer_name') }}</th>
                        <th>{{ translate('messages.customer_email') }}</th>
                        <th>{{ translate('messages.customer_mobile') }}</th>
                        <th>{{ translate('messages.address') }}</th>
                        <th>{{ translate('messages.dm') }}</th>
                        <th>{{ translate('messages.place_time') }}</th>
                        <th>{{ translate('messages.accepted_time') }}</th>
                        <th>{{ translate('messages.dispatched_time') }}</th>
                        <th>{{ translate('messages.completed_time') }}</th>
                        <th>{{ translate('messages.vendor_zone') }}</th>
                    </tr>
                </thead>

                <tbody id="set-rows">
                    @foreach ($orders as $key => $order)
                    <tr>
                        <td>{{ $orders->firstItem() + $key }}</td>
                        <td>
                            @if (isset($order->schedule_at))
                            {{ date('Y-m-d', strtotime($order->schedule_at)) }}
                            @else
                            {{ date('Y-m-d', strtotime($order->created_at)) }}
                            @endif
                        </td>
                        <td>{{ $order->id }}</td>
                        <td>{{ $order->store?$order->store->vendor_id:translate('messages.store_not_found') }}</td>
                        <td>{{ $order->store?$order->store->name : translate('messages.store_not_found') }}</td>
                        <td>{{ $order->store_id }}</td>
                        <td class="text-capitalize">{{ str_replace('_', ' ', $order['payment_method']) }}</td>
                        <td>{{ $order->transaction ? $order->transaction->store_amount - $order->transaction->tax : 0 }}
                        </td>
                        <td>{{ $order->details_sum_price }}</td>
                        <td>{{ $order->delivery_charge }}</td>
                        <td>{{ $order->transaction ? $order->transaction->tax : 0 }}</td>
                        <td>{{ $order->order_amount }}</td>
                        <td>{{ $order->coupon_code }}</td>
                        <td>{{ $order->coupon_discount_amount }}</td>
                        <td>{{ $order->transaction ? $order->transaction->admin_commission : 0 }}</td>
                        <td>{{ $order->user_id }}</td>
                        <td>{{ $order->customer ? $order->customer->f_name .''. $order->customer->l_name :
                            translate('messages.customer_not_found')}}</td>
                        <td>{{ $order->customer ? $order->customer->email : translate('messages.customer_not_found')}}
                        </td>
                        <td>{{ $order->customer ? $order->customer->phone : translate('messages.customer_not_found')}}
                        </td>
                        <td>
                            @php
                            $json = json_decode($order->delivery_address, true);
                            @endphp
                            {{ isset($json) ? $json['address'] : ' ' }}
                        </td>

                        <td>{{ isset($order->delivery_man) ? $order->delivery_man->f_name.'
                            '.$order->delivery_man->l_name : translate('messages.not_found') }} </td>
                        <td>
                            @if (isset($order->created_at))
                            {{ date('Y-m-d h:i a', strtotime($order->created_at)) }}
                            @else
                            {{ date('Y-m-d h:i a', strtotime($order->schedule_at)) }}
                            @endif
                        </td>
                        <td>{{date('Y-m-d h:i a', strtotime($order->confirmed))}}</td>
                        <td>{{date('Y-m-d h:i a', strtotime($order->handover))}}</td>
                        <td>{{date('Y-m-d h:i a', strtotime($order->delivered))}}</td>
                        <td>{{ ($order->store && $order->store->zone)? $order->store->zone->name
                            :translate('zone_deleted') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <!-- End Table -->

        <!-- Footer -->
        <div class="card-footer">
            <!-- Pagination -->
            <div class="row justify-content-end align-items-sm-center">
                <div class="col-sm-auto">
                    <div class="d-flex justify-content-center justify-content-sm-end">
                        <!-- Pagination -->
                        {!! $orders->appends($_GET)->links() !!}
                    </div>
                </div>
            </div>
            <!-- End Pagination -->
        </div>
        <!-- End Footer -->
    </div>
    <!-- End Card -->
    <!-- Order Filter Modal -->

    @endsection

    @push('script_2')
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
            
    </script>
    @endpush