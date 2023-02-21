<?php

namespace App\Http\Controllers\Admin;

use App\CentralLogics\OrderLogic;
use App\CentralLogics\StoreLogic;
use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\Item;
use App\Models\Order;
use App\Models\OrderTransaction;
use App\Models\Zone;
use App\Models\Store;
use Illuminate\Http\Request;
use Rap2hpoutre\FastExcel\FastExcel;
use App\Scopes\StoreScope;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function order_index()
    {
        if (session()->has('from_date') == false) {
            session()->put('from_date', date('Y-m-01'));
            session()->put('to_date', date('Y-m-30'));
        }
        return view('admin-views.report.order-index');
    }

    public function day_wise_report(Request $request)
    {
        if (session()->has('from_date') == false) {
            session()->put('from_date', date('Y-m-01'));
            session()->put('to_date', date('Y-m-30'));
        }
        $from = session('from_date');
        $to = session('to_date');
        $zone_id = $request->query('zone_id', isset(auth('admin')->user()->zone_id) ? auth('admin')->user()->zone_id : 'all');
        $zone = is_numeric($zone_id) ? Zone::findOrFail($zone_id) : null;

        $order_transactions = OrderTransaction::when(isset($zone), function ($query) use ($zone) {
            return $query->where('zone_id', $zone->id);
        })
            ->when(request('module_id'), function ($query) {
                return $query->module(request('module_id'));
            })
            ->whereBetween('created_at', [$from . ' 00:00:00', $to . ' 23:59:59'])->orderBy('created_at', 'desc')->paginate(config('default_pagination'))->withQueryString();
        return view('admin-views.report.day-wise-report', compact('order_transactions', 'zone'));
    }

    public function day_wise_export(Request $request)
    {
        if (session()->has('from_date') == false) {
            session()->put('from_date', date('Y-m-01'));
            session()->put('to_date', date('Y-m-30'));
        }
        $from = session('from_date');
        $to = session('to_date');
        $zone_id = $request->query('zone_id', isset(auth('admin')->user()->zone_id) ? auth('admin')->user()->zone_id : 'all');
        $zone = is_numeric($zone_id) ? Zone::findOrFail($zone_id) : null;

        $order_transactions = OrderTransaction::when(isset($zone), function ($query) use ($zone) {
            return $query->where('zone_id', $zone->id);
        })
            ->when(request('module_id'), function ($query) {
                return $query->module(request('module_id'));
            })
            ->whereBetween('created_at', [$from, $to])->get();

        if ($request->type == 'excel') {
            return (new FastExcel($order_transactions))->download('DayWiseReport.xlsx');
        } elseif ($request->type == 'csv') {
            return (new FastExcel($order_transactions))->download('DayWiseReport.csv');
        }
    }

    public function item_wise_report(Request $request)
    {
        if (session()->has('from_date') == false) {
            session()->put('from_date', now()->firstOfMonth()->format('Y-m-d'));
            session()->put('to_date', now()->lastOfMonth()->format('Y-m-d'));
        }
        $from = session('from_date');
        $to = session('to_date');

        $zone_id = $request->query('zone_id', isset(auth('admin')->user()->zone_id) ? auth('admin')->user()->zone_id : 'all');
        $store_id = $request->query('store_id', 'all');
        $zone = is_numeric($zone_id) ? Zone::findOrFail($zone_id) : null;
        $store = is_numeric($store_id) ? Store::findOrFail($store_id) : null;
        $items = \App\Models\Item::withoutGlobalScope(StoreScope::class)->withCount([
            'orders' => function ($query) use ($from, $to) {
                $query->whereBetween('created_at', [$from . ' 00:00:00', $to . ' 23:59:29']);
            },
        ])
            ->when($request->query('module_id', null), function ($query) use ($request) {
                return $query->module($request->query('module_id'));
            })
            ->when(isset($zone), function ($query) use ($zone) {
                return $query->whereIn('store_id', $zone->stores->pluck('id'));
            })
            ->when(isset($store), function ($query) use ($store) {
                return $query->where('store_id', $store->id);
            })
            ->paginate(config('default_pagination'))->withQueryString();

        return view('admin-views.report.item-wise-report', compact('zone', 'store', 'items'));
    }
    public function item_wise_export(Request $request)
    {
        if (session()->has('from_date') == false) {
            session()->put('from_date', now()->firstOfMonth()->format('Y-m-d'));
            session()->put('to_date', now()->lastOfMonth()->format('Y-m-d'));
        }
        $from = session('from_date');
        $to = session('to_date');

        $zone_id = $request->query('zone_id', isset(auth('admin')->user()->zone_id) ? auth('admin')->user()->zone_id : 'all');
        $store_id = $request->query('store_id', 'all');
        $zone = is_numeric($zone_id) ? Zone::findOrFail($zone_id) : null;
        $store = is_numeric($store_id) ? Store::findOrFail($store_id) : null;
        $items = \App\Models\Item::withoutGlobalScope(StoreScope::class)->withCount([
            'orders' => function ($query) use ($from, $to) {
                $query->whereBetween('created_at', [$from . ' 00:00:00', $to . ' 23:59:29']);
            },
        ])
            ->when($request->query('module_id', null), function ($query) use ($request) {
                return $query->module($request->query('module_id'));
            })
            ->when(isset($zone), function ($query) use ($zone) {
                return $query->whereIn('store_id', $zone->stores->pluck('id'));
            })
            ->when(isset($store), function ($query) use ($store) {
                return $query->where('store_id', $store->id);
            })
            ->get();

        if ($request->type == 'excel') {
            return (new FastExcel($items))->download('ItemReport.xlsx');
        } elseif ($request->type == 'csv') {
            return (new FastExcel($items))->download('ItemReport.csv');
        }
    }

    public function stock_report(Request $request)
    {
        $zone_id = $request->query('zone_id', isset(auth('admin')->user()->zone_id) ? auth('admin')->user()->zone_id : 'all');
        $store_id = $request->query('store_id', 'all');
        $zone = is_numeric($zone_id) ? Zone::findOrFail($zone_id) : null;
        $store = is_numeric($store_id) ? Store::findOrFail($store_id) : null;
        $stock_modules = array_keys(array_filter(config('module'), function ($var) {
            if (isset($var['stock']) && $var['stock']) return $var;
        }));
        $key = isset($request['search']) ? explode(' ', $request['search']) : [];

        $items = Item::withoutGlobalScope(StoreScope::class)->whereHas('store.module', function ($query) use ($stock_modules) {
            $query->whereIn('module_type', $stock_modules);
        })
            ->when($request->query('module_id', null), function ($query) use ($request) {
                return $query->module($request->query('module_id'));
            })
            ->when(isset($zone), function ($query) use ($zone) {
                return $query->whereIn('store_id', $zone->stores->pluck('id'));
            })
            ->when(isset($store), function ($query) use ($store) {
                return $query->where('store_id', $store->id);
            })
            ->when(count($key), function ($query) use ($key) {
                return $query->where(function ($q) use ($key) {
                    foreach ($key as $value) {
                        $q->orWhere('name', 'like', "%{$value}%");
                    }
                });
            })
            ->orderBy('stock')
            ->paginate(config('default_pagination'))->withQueryString();

        return view('admin-views.report.stock-report', compact('zone', 'store', 'items'));
    }

    public function stock_wise_export(Request $request)
    {
        $zone_id = $request->query('zone_id', isset(auth('admin')->user()->zone_id) ? auth('admin')->user()->zone_id : 'all');
        $store_id = $request->query('store_id', 'all');
        $zone = is_numeric($zone_id) ? Zone::findOrFail($zone_id) : null;
        $store = is_numeric($store_id) ? Store::findOrFail($store_id) : null;
        $stock_modules = array_keys(array_filter(config('module'), function ($var) {
            if (isset($var['stock']) && $var['stock']) return $var;
        }));
        $key = isset($request['search']) ? explode(' ', $request['search']) : [];

        $items = Item::withoutGlobalScope(StoreScope::class)->whereHas('store.module', function ($query) use ($stock_modules) {
            $query->whereIn('module_type', $stock_modules);
        })
            ->when($request->query('module_id', null), function ($query) use ($request) {
                return $query->module($request->query('module_id'));
            })
            ->when(isset($zone), function ($query) use ($zone) {
                return $query->whereIn('store_id', $zone->stores->pluck('id'));
            })
            ->when(isset($store), function ($query) use ($store) {
                return $query->where('store_id', $store->id);
            })
            ->when(count($key), function ($query) use ($key) {
                return $query->where(function ($q) use ($key) {
                    foreach ($key as $value) {
                        $q->orWhere('name', 'like', "%{$value}%");
                    }
                });
            })
            ->orderBy('stock')
            ->get();

        if ($request->type == 'excel') {
            return (new FastExcel($items))->download('StockReport.xlsx');
        } elseif ($request->type == 'csv') {
            return (new FastExcel($items))->download('StockReport.csv');
        }
    }

    public function order_transaction()
    {
        $order_transactions = OrderTransaction::latest()->paginate(config('default_pagination'));
        return view('admin-views.report.order-transactions', compact('order_transactions'));
    }


    public function set_date(Request $request)
    {
        session()->put('from_date', date('Y-m-d', strtotime($request['from'])));
        session()->put('to_date', date('Y-m-d', strtotime($request['to'])));
        return back();
    }

    public function item_search(Request $request)
    {
        $key = explode(' ', $request['search']);

        $from = session('from_date');
        $to = session('to_date');

        $zone_id = $request->query('zone_id', isset(auth('admin')->user()->zone_id) ? auth('admin')->user()->zone_id : 'all');
        $store_id = $request->query('store_id', 'all');
        $zone = is_numeric($zone_id) ? Zone::findOrFail($zone_id) : null;
        $store = is_numeric($store_id) ? Store::findOrFail($store_id) : null;
        $items = \App\Models\Item::withoutGlobalScope(StoreScope::class)->withCount([
            'orders as order_count' => function ($query) use ($from, $to) {
                $query->whereBetween('created_at', [$from, $to]);
            },
        ])
            ->when(isset($zone), function ($query) use ($zone) {
                return $query->whereIn('store_id', $zone->stores->pluck('id'));
            })
            ->when(isset($store), function ($query) use ($store) {
                return $query->where('store_id', $store->id);
            })
            ->where(function ($q) use ($key) {
                foreach ($key as $value) {
                    $q->orWhere('name', 'like', "%{$value}%");
                }
            })
            ->limit(25)->get();

        return response()->json([
            'count' => count($items),
            'view' => view('admin-views.report.partials._item_table', compact('items'))->render()
        ]);
    }

    public function stock_search(Request $request)
    {
        $key = explode(' ', $request['search']);

        $zone_id = $request->query('zone_id', isset(auth('admin')->user()->zone_id) ? auth('admin')->user()->zone_id : 'all');
        $store_id = $request->query('store_id', 'all');
        $zone = is_numeric($zone_id) ? Zone::findOrFail($zone_id) : null;
        $store = is_numeric($store_id) ? Store::findOrFail($store_id) : null;
        $stock_modules = array_keys(array_filter(config('module'), function ($var) {
            if (isset($var['stock']) && $var['stock']) return $var;
        }));
        $key = isset($request['search']) ? explode(' ', $request['search']) : [];

        $items = Item::withoutGlobalScope(StoreScope::class)->whereHas('store.module', function ($query) use ($stock_modules) {
            $query->whereIn('module_type', $stock_modules);
        })
            ->when($request->query('module_id', null), function ($query) use ($request) {
                return $query->module($request->query('module_id'));
            })
            ->when(isset($zone), function ($query) use ($zone) {
                return $query->whereIn('store_id', $zone->stores->pluck('id'));
            })
            ->when(isset($store), function ($query) use ($store) {
                return $query->where('store_id', $store->id);
            })
            ->when(count($key), function ($query) use ($key) {
                return $query->where(function ($q) use ($key) {
                    foreach ($key as $value) {
                        $q->orWhere('name', 'like', "%{$value}%");
                    }
                });
            })
            ->limit(25)->get();

        return response()->json([
            'count' => count($items),
            'view' => view('admin-views.report.partials._stock_table', compact('items'))->render()
        ]);
    }

    public function day_search(Request $request)
    {
        $key = explode(' ', $request['search']);

        $from = session('from_date');
        $to = session('to_date');

        $from = session('from_date');
        $to = session('to_date');
        $zone_id = $request->query('zone_id', isset(auth('admin')->user()->zone_id) ? auth('admin')->user()->zone_id : 'all');
        $zone = is_numeric($zone_id) ? Zone::findOrFail($zone_id) : null;

        $order_transactions = OrderTransaction::when(isset($zone), function ($query) use ($zone) {
            return $query->where('zone_id', $zone->id);
        })
            ->when(request('module_id'), function ($query) {
                return $query->module(request('module_id'));
            })
            ->whereBetween('created_at', [$from, $to])
            ->where(function ($q) use ($key) {
                foreach ($key as $value) {
                    $q->orWhere('order_id', 'like', "%{$value}%");
                }
            })
            ->limit(25)->get();

        return response()->json([
            'count' => count($order_transactions),
            'view' => view('admin-views.report.partials._day_table', compact('order_transactions'))->render()
        ]);
    }

    public function store_summary_report(Request $request)
    {
        $months = array(
            '"Jan"',
            '"Feb"',
            '"Mar"',
            '"Apr"',
            '"May"',
            '"Jun"',
            '"Jul"',
            '"Aug"',
            '"Sep"',
            '"Oct"',
            '"Nov"',
            '"Dec"'
        );
        $days = array(
            '"Sun"',
            '"Mon"',
            '"Tue"',
            '"Wed"',
            '"Thu"',
            '"Fri"',
            '"Sat"'
        );

        $filter = $request->query('filter', 'all_time');

        $stores = Store::with('orders')
            ->when(isset($filter) && $filter == 'all_time', function ($query) {
                return $query->with([
                    'orders' => function ($query) {
                        $query->StoreOrder()->Delivered()->NotRefunded();
                    },
                ]);
            })
            ->when(isset($filter) && $filter == 'this_year', function ($query) {
                return $query->with([
                    'orders' => function ($query) {
                        $query->StoreOrder()->Delivered()->NotRefunded()->whereYear('schedule_at', now()->format('Y'));
                    },
                ]);
            })
            ->when(isset($filter) && $filter == 'this_month', function ($query) {
                return $query->with([
                    'orders' => function ($query) {
                        $query->StoreOrder()->Delivered()->NotRefunded()->whereMonth('schedule_at', now()->format('m'))->whereYear('schedule_at', now()->format('Y'));
                    },
                ]);
            })
            ->when(isset($filter) && $filter == 'previous_year', function ($query) {
                return $query->with([
                    'orders' => function ($query) {
                        $query->StoreOrder()->Delivered()->NotRefunded()->whereYear('schedule_at', date('Y') - 1);
                    },
                ]);
            })
            ->when(isset($filter) && $filter == 'this_week', function ($query) {
                return $query->with([
                    'orders' => function ($query) {
                        $query->StoreOrder()->Delivered()->NotRefunded()->whereBetween('schedule_at', [now()->startOfWeek()->format('Y-m-d H:i:s'), now()->endOfWeek()->format('Y-m-d H:i:s')]);
                    },
                ]);
            })
            ->orderBy('order_count', 'DESC')->paginate(config('default_pagination'));

        $new_stores = Store::when(isset($filter) && $filter == 'this_year', function ($query) {
            return $query->whereYear('created_at', now()->format('Y'));
        })
            ->when(isset($filter) && $filter == 'this_month', function ($query) {
                return $query->whereMonth('created_at', now()->format('m'))->whereYear('created_at', now()->format('Y'));
            })
            ->when(isset($filter) && $filter == 'this_month', function ($query) {
                return $query->whereMonth('created_at', now()->format('m'))->whereYear('created_at', now()->format('Y'));
            })
            ->when(isset($filter) && $filter == 'previous_year', function ($query) {
                return $query->whereYear('created_at', date('Y') - 1);
            })
            ->when(isset($filter) && $filter == 'this_week', function ($query) {
                return $query->whereBetween('created_at', [now()->startOfWeek()->format('Y-m-d H:i:s'), now()->endOfWeek()->format('Y-m-d H:i:s')]);
            })->Active()->count();
        //   dd($new_stores);
        $order_payment_methods = Order::when(isset($filter) && $filter == 'this_year', function ($query) {
            return $query->whereYear('schedule_at', now()->format('Y'));
        })
            ->when(isset($filter) && $filter == 'this_month', function ($query) {
                return $query->whereMonth('schedule_at', now()->format('m'))->whereYear('schedule_at', now()->format('Y'));
            })
            ->when(isset($filter) && $filter == 'this_month', function ($query) {
                return $query->whereMonth('schedule_at', now()->format('m'))->whereYear('schedule_at', now()->format('Y'));
            })
            ->when(isset($filter) && $filter == 'previous_year', function ($query) {
                return $query->whereYear('schedule_at', date('Y') - 1);
            })
            ->when(isset($filter) && $filter == 'this_week', function ($query) {
                return $query->whereBetween('schedule_at', [now()->startOfWeek()->format('Y-m-d H:i:s'), now()->endOfWeek()->format('Y-m-d H:i:s')]);
            })
            ->StoreOrder()->Delivered()->NotRefunded()
            ->selectRaw(DB::raw("sum(`order_amount`) as total_order_amount, count(*) as order_count, IF((`payment_method`='cash_on_delivery'), `payment_method`, IF(`payment_method`='wallet',`payment_method`, 'digital_payment')) as 'payment_methods'"))->groupBy('payment_methods')
            ->get();

        $orders = Order::when(isset($filter) && $filter == 'this_year', function ($query) {
            return $query->whereYear('schedule_at', now()->format('Y'));
        })
            ->when(isset($filter) && $filter == 'this_month', function ($query) {
                return $query->whereMonth('schedule_at', now()->format('m'))->whereYear('schedule_at', now()->format('Y'));
            })
            ->when(isset($filter) && $filter == 'this_month', function ($query) {
                return $query->whereMonth('schedule_at', now()->format('m'))->whereYear('schedule_at', now()->format('Y'));
            })
            ->when(isset($filter) && $filter == 'previous_year', function ($query) {
                return $query->whereYear('schedule_at', date('Y') - 1);
            })
            ->when(isset($filter) && $filter == 'this_week', function ($query) {
                return $query->whereBetween('schedule_at', [now()->startOfWeek()->format('Y-m-d H:i:s'), now()->endOfWeek()->format('Y-m-d H:i:s')]);
            })->StoreOrder()->get();
        $total_order_amount = $orders->whereIn('order_status', ['delivered'])->sum('order_amount');
        $total_ongoing = $orders->whereIn('order_status', ['pending', 'accepted', 'confirmed', 'processing', 'handover', 'picked_up'])->count();
        $total_canceled = $orders->whereIn('order_status', ['failed', 'canceled'])->count();
        $total_delivered = $orders->whereIn('order_status', ['delivered'])->count();

        $items = Item::when(isset($filter) && $filter == 'this_year', function ($query) {
            return $query->whereYear('created_at', now()->format('Y'));
        })
            ->when(isset($filter) && $filter == 'this_month', function ($query) {
                return $query->whereMonth('created_at', now()->format('m'))->whereYear('created_at', now()->format('Y'));
            })
            ->when(isset($filter) && $filter == 'this_month', function ($query) {
                return $query->whereMonth('created_at', now()->format('m'))->whereYear('created_at', now()->format('Y'));
            })
            ->when(isset($filter) && $filter == 'previous_year', function ($query) {
                return $query->whereYear('created_at', date('Y') - 1);
            })
            ->when(isset($filter) && $filter == 'this_week', function ($query) {
                return $query->whereBetween('created_at', [now()->startOfWeek()->format('Y-m-d H:i:s'), now()->endOfWeek()->format('Y-m-d H:i:s')]);
            })->get();

        $monthly_order = [];
        switch ($filter) {
            case "all_time":
                $monthly_order = Order::select(
                    DB::raw("(sum(order_amount)) as order_amount"),
                    DB::raw("(DATE_FORMAT(schedule_at, '%Y')) as year")
                )
                    ->StoreOrder()->Delivered()->NotRefunded()
                    ->groupBy(DB::raw("DATE_FORMAT(schedule_at, '%Y')"))
                    ->get()->toArray();

                $label = array_map(function ($order) {
                    return $order['year'];
                }, $monthly_order);
                $data = array_map(function ($order) {
                    return $order['order_amount'];
                }, $monthly_order);
                break;
            case "this_year":
                for ($i = 1; $i <= 12; $i++) {
                    $monthly_order[$i] = Order::StoreOrder()->Delivered()->NotRefunded()->whereMonth('schedule_at', $i)->whereYear('schedule_at', now()->format('Y'))
                        ->sum('order_amount');
                }
                $label = $months;
                $data = $monthly_order;
                break;
            case "previous_year":
                for ($i = 1; $i <= 12; $i++) {
                    $monthly_order[$i] = Order::StoreOrder()->Delivered()->NotRefunded()->whereMonth('schedule_at', $i)->whereYear('schedule_at', date('Y') - 1)
                        ->sum('order_amount');
                }
                $label = $months;
                $data = $monthly_order;
                break;
            case "this_week":
                $weekStartDate = now()->startOfWeek();
                for ($i = 1; $i <= 7; $i++) {
                    $monthly_order[$i] = Order::StoreOrder()->Delivered()->NotRefunded()->whereDay('schedule_at', $weekStartDate->format('d'))->whereMonth('schedule_at', now()->format('m'))
                        ->sum('order_amount');

                    $weekStartDate = $weekStartDate->addDays(1);
                }
                $label = $days;
                $data = $monthly_order;
                break;
            case "this_month":
                $start = now()->startOfMonth();
                $end = now()->startOfMonth()->addDays(7);
                $total_day = now()->daysInMonth;
                $remaining_days = now()->daysInMonth - 28;
                $weeks = array(
                    '"Day 1-7"',
                    '"Day 8-14"',
                    '"Day 15-21"',
                    '"Day 22-' . $total_day . '"',
                );
                for ($i = 1; $i <= 4; $i++) {
                    $monthly_order[$i] = Order::StoreOrder()->Delivered()->NotRefunded()
                        ->whereBetween('schedule_at', ["{$start->format('Y-m-d')} 00:00:00", "{$end->format('Y-m-d')} 23:59:59"])
                        ->sum('order_amount');
                    $start = $start->addDays(7);
                    $end = $i == 3 ? $end->addDays(7 + $remaining_days) : $end->addDays(7);
                }
                $label = $weeks;
                $data = $monthly_order;
                break;
            default:
                for ($i = 1; $i <= 12; $i++) {
                    $monthly_order[$i] = Order::StoreOrder()->Delivered()->NotRefunded()->whereMonth('schedule_at', $i)->whereYear('schedule_at', now()->format('Y'))
                        ->sum('order_amount');
                }
                $label = $months;
                $data = $monthly_order;
        }

        return view('admin-views.report.store-summary-report', compact('stores', 'new_stores', 'orders', 'order_payment_methods', 'items', 'monthly_order', 'label', 'data', 'filter', 'total_order_amount', 'total_ongoing', 'total_canceled', 'total_delivered'));
    }

    public function store_summary_search(Request $request)
    {
        $key = explode(' ', $request['search']);

        $filter = $request->query('filter', 'all_time');

        $stores = Store::with('orders')
            ->when(isset($filter) && $filter == 'this_year', function ($query) {
                return $query->with([
                    'orders' => function ($query) {
                        $query->whereYear('schedule_at', now()->format('Y'));
                    },
                ]);
            })
            ->when(isset($filter) && $filter == 'this_month', function ($query) {
                return $query->with([
                    'orders' => function ($query) {
                        $query->whereMonth('schedule_at', now()->format('m'))->whereYear('schedule_at', now()->format('Y'));
                    },
                ]);
            })
            ->when(isset($filter) && $filter == 'previous_year', function ($query) {
                return $query->with([
                    'orders' => function ($query) {
                        $query->whereYear('schedule_at', date('Y') - 1);
                    },
                ]);
            })
            ->when(isset($filter) && $filter == 'this_week', function ($query) {
                return $query->with([
                    'orders' => function ($query) {
                        $query->whereBetween('schedule_at', [now()->startOfWeek()->format('Y-m-d H:i:s'), now()->endOfWeek()->format('Y-m-d H:i:s')]);
                    },
                ]);
            })
            ->where(function ($q) use ($key) {
                foreach ($key as $value) {
                    $q->orWhere('name', 'like', "%{$value}%");
                }
            })->Active()
            ->limit(25)->get();

        return response()->json([
            'count' => count($stores),
            'view' => view('admin-views.report.partials._store_summary_table', compact('stores'))->render()
        ]);
    }

    public function store_sales_report(Request $request)
    {
        if (session()->has('from_date') == false) {
            session()->put('from_date', now()->firstOfMonth()->format('Y-m-d'));
            session()->put('to_date', now()->lastOfMonth()->format('Y-m-d'));
        }
        $from = session('from_date');
        $to = session('to_date');

        $months = array(
            '"Jan"',
            '"Feb"',
            '"Mar"',
            '"Apr"',
            '"May"',
            '"Jun"',
            '"Jul"',
            '"Aug"',
            '"Sep"',
            '"Oct"',
            '"Nov"',
            '"Dec"'
        );
        $days = array(
            '"Sun"',
            '"Mon"',
            '"Tue"',
            '"Wed"',
            '"Thu"',
            '"Fri"',
            '"Sat"'
        );

        $zone_id = $request->query('zone_id', isset(auth('admin')->user()->zone_id) ? auth('admin')->user()->zone_id : 'all');
        $store_id = $request->query('store_id', 'all');
        $filter = $request->query('filter', 'all_time');
        $zone = is_numeric($zone_id) ? Zone::findOrFail($zone_id) : null;
        $store = is_numeric($store_id) ? Store::findOrFail($store_id) : null;

        // items
        $items = Item::with('orders')->when(isset($from) && isset($to) && $from != null && $to != null && $filter == 'custom', function ($query) use ($from, $to) {
                return $query->with([
                    'orders' => function ($query) use ($from, $to) {
                        $query->whereBetween('created_at', [$from . ' 00:00:00', $to . ' 23:59:29']);
                    },
                ]);
            })
            ->when(isset($filter) && $filter == 'this_year', function ($query) {
                return $query->with([
                    'orders' => function ($query) {
                        $query->whereYear('created_at', now()->format('Y'));
                    },
                ]);
            })
            ->when(isset($filter) && $filter == 'this_month', function ($query) {
                return $query->with([
                    'orders' => function ($query) {
                        $query->whereMonth('created_at', now()->format('m'))->whereYear('created_at', now()->format('Y'));
                    },
                ]);
            })
            ->when(isset($filter) && $filter == 'previous_year', function ($query) {
                return $query->with([
                    'orders' => function ($query) {
                        $query->whereYear('created_at', date('Y') - 1);
                    },
                ]);
            })
            ->when(isset($filter) && $filter == 'this_week', function ($query) {
                return $query->with([
                    'orders' => function ($query) {
                        $query->whereBetween('created_at', [now()->startOfWeek()->format('Y-m-d H:i:s'), now()->endOfWeek()->format('Y-m-d H:i:s')]);
                    },
                ]);
            })
            ->when(isset($zone), function ($query) use ($zone) {
                return $query->whereIn('store_id', $zone->stores->pluck('id'));
            })
            ->when(isset($store), function ($query) use ($store) {
                return $query->where('store_id', $store->id);
            })
            ->paginate(config('default_pagination'));

        // order list with pagination


        $orders = Order::with('transaction')->when(isset($zone), function ($query) use ($zone) {
            return $query->whereIn('store_id', $zone->stores->pluck('id'));
        })
            ->when(isset($store), function ($query) use ($store) {
                return $query->where('store_id', $store->id);
            })
            ->when(isset($from) && isset($to) && $from != null && $to != null && $filter == 'custom', function ($query) use ($from, $to) {
                return $query->whereBetween('schedule_at', [$from, $to]);
            })
            ->when(isset($filter) && $filter == 'this_year', function ($query) {
                return $query->whereYear('schedule_at', now()->format('Y'));
            })
            ->when(isset($filter) && $filter == 'this_month', function ($query) {
                return $query->whereMonth('schedule_at', now()->format('m'))->whereYear('schedule_at', now()->format('Y'));
            })
            ->when(isset($filter) && $filter == 'this_month', function ($query) {
                return $query->whereMonth('schedule_at', now()->format('m'))->whereYear('schedule_at', now()->format('Y'));
            })
            ->when(isset($filter) && $filter == 'previous_year', function ($query) {
                return $query->whereYear('schedule_at', date('Y') - 1);
            })
            ->when(isset($filter) && $filter == 'this_week', function ($query) {
                return $query->whereBetween('schedule_at', [now()->startOfWeek()->format('Y-m-d H:i:s'), now()->endOfWeek()->format('Y-m-d H:i:s')]);
            })
            ->StoreOrder()
            ->Delivered()->NotRefunded()
            ->withSum('transaction', 'admin_commission')
            ->withSum('transaction', 'admin_expense')
            ->withSum('transaction', 'delivery_fee_comission')
            ->withSum('transaction', 'store_amount')
            ->get();

        // dd($orders[0]);

        // custom filtering for bar chart
        $monthly_order = [];
        $label = [];
        if ($filter != 'custom') {
            switch ($filter) {
                case "all_time":
                    $monthly_order = Order::StoreOrder()->Delivered()->NotRefunded()->when(isset($zone), function ($query) use ($zone) {
                        return $query->whereIn('store_id', $zone->stores->pluck('id'));
                    })
                        ->when(isset($store), function ($query) use ($store) {
                            return $query->where('store_id', $store->id);
                        })->select(
                            DB::raw("(sum(order_amount)) as order_amount"),
                            DB::raw("(DATE_FORMAT(schedule_at, '%Y')) as year")
                        )
                        ->groupBy(DB::raw("DATE_FORMAT(schedule_at, '%Y')"))
                        ->get()->toArray();

                    $label = array_map(function ($order) {
                        return $order['year'];
                    }, $monthly_order);
                    $data = array_map(function ($order) {
                        return $order['order_amount'];
                    }, $monthly_order);
                    break;
                case "this_year":
                    for ($i = 1; $i <= 12; $i++) {
                        $monthly_order[$i] = Order::StoreOrder()->Delivered()->NotRefunded()->when(isset($zone), function ($query) use ($zone) {
                            return $query->whereIn('store_id', $zone->stores->pluck('id'));
                        })
                            ->when(isset($store), function ($query) use ($store) {
                                return $query->where('store_id', $store->id);
                            })->whereMonth('schedule_at', $i)->whereYear('schedule_at', now()->format('Y'))
                            ->sum('order_amount');
                    }
                    $label = $months;
                    $data = $monthly_order;
                    break;
                case "previous_year":
                    for ($i = 1; $i <= 12; $i++) {
                        $monthly_order[$i] = Order::StoreOrder()->Delivered()->NotRefunded()->when(isset($zone), function ($query) use ($zone) {
                            return $query->whereIn('store_id', $zone->stores->pluck('id'));
                        })
                            ->when(isset($store), function ($query) use ($store) {
                                return $query->where('store_id', $store->id);
                            })->whereMonth('schedule_at', $i)->whereYear('schedule_at', date('Y') - 1)
                            ->sum('order_amount');
                    }
                    $label = $months;
                    $data = $monthly_order;
                    break;
                case "this_week":
                    $weekStartDate = now()->startOfWeek();
                    for ($i = 1; $i <= 7; $i++) {
                        $monthly_order[$i] = Order::StoreOrder()->Delivered()->NotRefunded()->when(isset($zone), function ($query) use ($zone) {
                            return $query->whereIn('store_id', $zone->stores->pluck('id'));
                        })
                            ->when(isset($store), function ($query) use ($store) {
                                return $query->where('store_id', $store->id);
                            })->whereDay('schedule_at', $weekStartDate->format('d'))->whereMonth('schedule_at', now()->format('m'))
                            ->sum('order_amount');
                        $weekStartDate = $weekStartDate->addDays(1);
                    }
                    $label = $days;
                    $data = $monthly_order;
                    break;
                case "this_month":
                    $start = now()->startOfMonth();
                    $end = now()->startOfMonth()->addDays(6);
                    $total_day = now()->daysInMonth;
                    $remaining_days = now()->daysInMonth - 28;
                    $weeks = array(
                        '"Day 1-7"',
                        '"Day 8-14"',
                        '"Day 15-21"',
                        '"Day 22-' . $total_day . '"',
                    );
                    for ($i = 1; $i <= 4; $i++) {
                        $monthly_order[$i] = Order::StoreOrder()->Delivered()->NotRefunded()->when(isset($zone), function ($query) use ($zone) {
                            return $query->whereIn('store_id', $zone->stores->pluck('id'));
                        })
                            ->when(isset($store), function ($query) use ($store) {
                                return $query->where('store_id', $store->id);
                            })
                            ->whereBetween('schedule_at', ["{$start->format('Y-m-d')} 00:00:00", "{$end->format('Y-m-d')} 23:59:59"])
                            ->sum('order_amount');
                        $start = $start->addDays(7);
                        $end = $i == 3 ? $end->addDays(7 + $remaining_days) : $end->addDays(7);
                    }
                    $label = $weeks;
                    $data = $monthly_order;
                    break;
                default:
                    for ($i = 1; $i <= 12; $i++) {
                        $monthly_order[$i] = Order::StoreOrder()->Delivered()->NotRefunded()->when(isset($zone), function ($query) use ($zone) {
                            return $query->whereIn('store_id', $zone->stores->pluck('id'));
                        })
                            ->when(isset($store), function ($query) use ($store) {
                                return $query->where('store_id', $store->id);
                            })->whereMonth('schedule_at', $i)->whereYear('schedule_at', now()->format('Y'))
                            ->sum('order_amount');
                    }
                    $label = $months;
                    $data = $monthly_order;
            }
        } else {

            $to = Carbon::parse($to);
            $from = Carbon::parse($from);

            $years_count = $to->diffInYears($from);
            $months_count = $to->diffInMonths($from);
            $weeks_count = $to->diffInWeeks($from);
            $days_count = $to->diffInDays($from);


            if ($years_count > 0) {
                $monthly_order = Order::StoreOrder()->Delivered()->NotRefunded()->when(isset($zone), function ($query) use ($zone) {
                    return $query->whereIn('store_id', $zone->stores->pluck('id'));
                })
                    ->when(isset($store), function ($query) use ($store) {
                        return $query->where('store_id', $store->id);
                    })
                    ->whereBetween('schedule_at', ["{$from}", "{$to->format('Y-m-d')} 23:59:59"])
                    ->select(
                        DB::raw("(sum(order_amount)) as order_amount"),
                        DB::raw("(DATE_FORMAT(schedule_at, '%Y')) as year")
                    )
                    ->groupBy('year')
                    ->get()->toArray();

                $label = array_map(function ($order) {
                    return $order['year'];
                }, $monthly_order);
                $data = array_map(function ($order) {
                    return $order['order_amount'];
                }, $monthly_order);
            } elseif ($months_count > 0) {
                for ($i = (int)$from->format('m'); $i <= (int)$from->format('m') + $months_count; $i++) {
                    $monthly_order[$i] = Order::StoreOrder()->Delivered()->NotRefunded()->when(isset($zone), function ($query) use ($zone) {
                        return $query->whereIn('store_id', $zone->stores->pluck('id'));
                    })
                        ->when(isset($store), function ($query) use ($store) {
                            return $query->where('store_id', $store->id);
                        })->whereMonth('schedule_at', $i)
                        ->sum('order_amount');
                    $label[$i] = $months[$i - 1];
                }
                $label = $label;
                $data = $monthly_order;
            } elseif ($weeks_count > 0) {
                // $start = $from;
                // $end = $from->addDays(7);
                // $weeks = [];
                // for ($i = 1; $i <= 4; $i++) {
                //     $weeks[$i] = '"Day ' . (int)$start->format('d') . '-' . ((int)$start->format('d') + 7) . '"';
                //     $monthly_order[$i] = Order::when(isset($zone), function ($query) use ($zone) {
                //         return $query->whereIn('store_id', $zone->stores->pluck('id'));
                //     })
                //         ->when(isset($store), function ($query) use ($store) {
                //             return $query->where('store_id', $store->id);
                //         })
                //         ->whereBetween('schedule_at', [$start, "{$end->format('Y-m-d')} 23:59:59"])
                //         ->sum('order_amount');

                //     $start = $end;
                //     $end = $start->addDays(7);
                // }
                // $label = $weeks;
                // $data = $monthly_order;
                for ($i = (int)$from->format('d'); $i <= (int)$to->format('d'); $i++) {
                    $monthly_order[$i] = Order::StoreOrder()->Delivered()->NotRefunded()->when(isset($zone), function ($query) use ($zone) {
                        return $query->whereIn('store_id', $zone->stores->pluck('id'));
                    })
                        ->when(isset($store), function ($query) use ($store) {
                            return $query->where('store_id', $store->id);
                        })->whereDay('schedule_at', $i)->whereMonth('schedule_at', $from->format('m'))->whereYear('schedule_at', $from->format('Y'))
                        ->sum('order_amount');
                    $label[$i] = $i;
                }
                $label = $label;
                $data = $monthly_order;
            } elseif ($days_count >= 0) {
                for ($i = (int)$from->format('d'); $i <= (int)$to->format('d'); $i++) {
                    $monthly_order[$i] = Order::StoreOrder()->Delivered()->NotRefunded()->when(isset($zone), function ($query) use ($zone) {
                        return $query->whereIn('store_id', $zone->stores->pluck('id'));
                    })
                        ->when(isset($store), function ($query) use ($store) {
                            return $query->where('store_id', $store->id);
                        })->whereDay('schedule_at', $i)->whereMonth('schedule_at', $from->format('m'))->whereYear('schedule_at', $from->format('Y'))
                        ->sum('order_amount');
                    $label[$i] = $i;
                }
                $label = $label;
                $data = $monthly_order;
            }
        }

        return view('admin-views.report.store-sales-report', compact('zone', 'store', 'items', 'orders', 'data', 'label', 'filter'));
    }

    public function store_sales_search(Request $request)
    {
        $key = explode(' ', $request['search']);

        $from = session('from_date');
        $to = session('to_date');

        $zone_id = $request->query('zone_id', isset(auth('admin')->user()->zone_id) ? auth('admin')->user()->zone_id : 'all');
        $store_id = $request->query('store_id', 'all');
        $zone = is_numeric($zone_id) ? Zone::findOrFail($zone_id) : null;
        $store = is_numeric($store_id) ? Store::findOrFail($store_id) : null;
        $items = \App\Models\Item::withoutGlobalScope(StoreScope::class)->with('orders')->withCount([
            'orders' => function ($query) use ($from, $to) {
                $query->whereBetween('schedule_at', [$from . ' 00:00:00', $to . ' 23:59:29']);
            },
        ])
            ->when(isset($zone), function ($query) use ($zone) {
                return $query->whereIn('store_id', $zone->stores->pluck('id'));
            })
            ->when(isset($store), function ($query) use ($store) {
                return $query->where('store_id', $store->id);
            })->where(function ($q) use ($key) {
                foreach ($key as $value) {
                    $q->orWhere('name', 'like', "%{$value}%");
                }
            })
            ->limit(25)->get();

        return response()->json([
            'count' => count($items),
            'view' => view('admin-views.report.partials._store_sale_table', compact('items'))->render()
        ]);
    }

    public function store_order_report(Request $request)
    {
        if (session()->has('from_date') == false) {
            session()->put('from_date', now()->firstOfMonth()->format('Y-m-d'));
            session()->put('to_date', now()->lastOfMonth()->format('Y-m-d'));
        }
        $from = session('from_date');
        $to = session('to_date');

        $months = array(
            '"Jan"',
            '"Feb"',
            '"Mar"',
            '"Apr"',
            '"May"',
            '"Jun"',
            '"Jul"',
            '"Aug"',
            '"Sep"',
            '"Oct"',
            '"Nov"',
            '"Dec"'
        );
        $days = array(
            '"Sun"',
            '"Mon"',
            '"Tue"',
            '"Wed"',
            '"Thu"',
            '"Fri"',
            '"Sat"'
        );

        $zone_id = $request->query('zone_id', isset(auth('admin')->user()->zone_id) ? auth('admin')->user()->zone_id : 'all');
        $store_id = $request->query('store_id', 'all');
        $filter = $request->query('filter', 'all_time');
        $zone = is_numeric($zone_id) ? Zone::findOrFail($zone_id) : null;
        $store = is_numeric($store_id) ? Store::findOrFail($store_id) : null;

        // order list with pagination
        $orders = Order::with(['customer', 'store'])
            ->when(isset($zone), function ($query) use ($zone) {
                return $query->whereIn('store_id', $zone->stores->pluck('id'));
            })
            ->when(isset($store), function ($query) use ($store) {
                return $query->where('store_id', $store->id);
            })
            ->when(isset($from) && isset($to) && $from != null && $to != null && $filter == 'custom', function ($query) use ($from, $to) {
                return $query->whereBetween('schedule_at', [$from . " 00:00:00", $to . " 23:59:59"]);
            })
            ->when(isset($filter) && $filter == 'this_year', function ($query) {
                return $query->whereYear('schedule_at', now()->format('Y'));
            })
            ->when(isset($filter) && $filter == 'this_month', function ($query) {
                return $query->whereMonth('schedule_at', now()->format('m'))->whereYear('schedule_at', now()->format('Y'));
            })
            ->when(isset($filter) && $filter == 'this_month', function ($query) {
                return $query->whereMonth('schedule_at', now()->format('m'))->whereYear('schedule_at', now()->format('Y'));
            })
            ->when(isset($filter) && $filter == 'previous_year', function ($query) {
                return $query->whereYear('schedule_at', date('Y') - 1);
            })
            ->when(isset($filter) && $filter == 'this_week', function ($query) {
                return $query->whereBetween('schedule_at', [now()->startOfWeek()->format('Y-m-d H:i:s'), now()->endOfWeek()->format('Y-m-d H:i:s')]);
            })
            ->StoreOrder()->NotRefunded()
            ->withSum('transaction', 'admin_commission')
            ->withSum('transaction', 'admin_expense')
            ->withSum('transaction', 'delivery_fee_comission')
            ->orderBy('schedule_at', 'desc')->paginate(config('default_pagination'));

        // order card values calculation
        $orders_list = Order::with(['customer', 'store'])
            ->when(isset($zone), function ($query) use ($zone) {
                return $query->whereIn('store_id', $zone->stores->pluck('id'));
            })
            ->when(isset($store), function ($query) use ($store) {
                return $query->where('store_id', $store->id);
            })
            ->when(isset($from) && isset($to) && $from != null && $to != null && $filter == 'custom', function ($query) use ($from, $to) {
                return $query->whereBetween('schedule_at', [$from . " 00:00:00", $to . " 23:59:59"]);
            })
            ->when(isset($filter) && $filter == 'this_year', function ($query) {
                return $query->whereYear('schedule_at', now()->format('Y'));
            })
            ->when(isset($filter) && $filter == 'this_month', function ($query) {
                return $query->whereMonth('schedule_at', now()->format('m'))->whereYear('schedule_at', now()->format('Y'));
            })
            ->when(isset($filter) && $filter == 'this_month', function ($query) {
                return $query->whereMonth('schedule_at', now()->format('m'))->whereYear('schedule_at', now()->format('Y'));
            })
            ->when(isset($filter) && $filter == 'previous_year', function ($query) {
                return $query->whereYear('schedule_at', date('Y') - 1);
            })
            ->when(isset($filter) && $filter == 'this_week', function ($query) {
                return $query->whereBetween('schedule_at', [now()->startOfWeek()->format('Y-m-d H:i:s'), now()->endOfWeek()->format('Y-m-d H:i:s')]);
            })
            ->StoreOrder()->NotRefunded()
            ->withSum('transaction', 'admin_commission')
            ->withSum('transaction', 'admin_expense')
            ->withSum('transaction', 'delivery_fee_comission')
            ->orderBy('schedule_at', 'desc')->get();

        $total_order_amount = $orders_list->sum('order_amount');
        $total_coupon_discount = $orders_list->sum('coupon_discount_amount');
        $total_product_discount = $orders_list->sum('store_discount_amount');

        $total_ongoing = $orders_list->whereIn('order_status', ['pending', 'accepted', 'confirmed', 'processing', 'handover', 'picked_up'])->sum('order_amount');
        $total_canceled = $orders_list->whereIn('order_status', ['failed', 'canceled'])->sum('order_amount');
        $total_delivered = $orders_list->where('order_status', 'delivered')->sum('order_amount');
        $total_ongoing_count = $orders_list->whereIn('order_status', ['pending', 'accepted', 'confirmed', 'processing', 'handover', 'picked_up'])->count();
        $total_canceled_count = $orders_list->whereIn('order_status', ['failed', 'canceled'])->count();
        $total_delivered_count = $orders_list->where('order_status', 'delivered')->count();

        // payment type statistics
        $order_payment_methods = Order::when(isset($zone), function ($query) use ($zone) {
            return $query->whereIn('store_id', $zone->stores->pluck('id'));
        })
            ->when(isset($store), function ($query) use ($store) {
                return $query->where('store_id', $store->id);
            })
            ->when(isset($from) && isset($to) && $from != null && $to != null && $filter == 'custom', function ($query) use ($from, $to) {
                return $query->whereBetween('schedule_at', [$from . " 00:00:00", $to . " 23:59:59"]);
            })
            ->when(isset($filter) && $filter == 'this_year', function ($query) {
                return $query->whereYear('schedule_at', now()->format('Y'));
            })
            ->when(isset($filter) && $filter == 'this_month', function ($query) {
                return $query->whereMonth('schedule_at', now()->format('m'))->whereYear('schedule_at', now()->format('Y'));
            })
            ->when(isset($filter) && $filter == 'this_month', function ($query) {
                return $query->whereMonth('schedule_at', now()->format('m'))->whereYear('schedule_at', now()->format('Y'));
            })
            ->when(isset($filter) && $filter == 'previous_year', function ($query) {
                return $query->whereYear('schedule_at', date('Y') - 1);
            })
            ->when(isset($filter) && $filter == 'this_week', function ($query) {
                return $query->whereBetween('schedule_at', [now()->startOfWeek()->format('Y-m-d H:i:s'), now()->endOfWeek()->format('Y-m-d H:i:s')]);
            })
            ->StoreOrder()->NotRefunded()
            ->selectRaw(DB::raw("sum(`order_amount`) as total_order_amount, count(*) as order_count, IF((`payment_method`='cash_on_delivery'), `payment_method`, IF(`payment_method`='wallet',`payment_method`, 'digital_payment')) as 'payment_methods'"))
            ->groupBy('payment_methods')
            ->get();

        // custom filtering for bar chart
        $monthly_order = [];
        $label = [];
        if ($filter != 'custom') {
            switch ($filter) {
                case "all_time":
                    $monthly_order = Order::when(isset($zone), function ($query) use ($zone) {
                        return $query->whereIn('store_id', $zone->stores->pluck('id'));
                    })
                        ->when(isset($store), function ($query) use ($store) {
                            return $query->where('store_id', $store->id);
                        })
                        ->StoreOrder()->NotRefunded()
                        ->select(
                            DB::raw("(sum(order_amount)) as order_amount"),
                            DB::raw("(DATE_FORMAT(schedule_at, '%Y')) as year")
                        )
                        ->groupBy(DB::raw("DATE_FORMAT(schedule_at, '%Y')"))
                        ->get()->toArray();

                    $label = array_map(function ($order) {
                        return $order['year'];
                    }, $monthly_order);
                    $data = array_map(function ($order) {
                        return $order['order_amount'];
                    }, $monthly_order);
                    break;
                case "this_year":
                    for ($i = 1; $i <= 12; $i++) {
                        $monthly_order[$i] = Order::when(isset($zone), function ($query) use ($zone) {
                            return $query->whereIn('store_id', $zone->stores->pluck('id'));
                        })
                            ->when(isset($store), function ($query) use ($store) {
                                return $query->where('store_id', $store->id);
                            })
                            ->StoreOrder()->NotRefunded()
                            ->whereMonth('schedule_at', $i)->whereYear('schedule_at', now()->format('Y'))
                            ->sum('order_amount');
                    }
                    $label = $months;
                    $data = $monthly_order;
                    break;
                case "previous_year":
                    for ($i = 1; $i <= 12; $i++) {
                        $monthly_order[$i] = Order::when(isset($zone), function ($query) use ($zone) {
                            return $query->whereIn('store_id', $zone->stores->pluck('id'));
                        })
                            ->when(isset($store), function ($query) use ($store) {
                                return $query->where('store_id', $store->id);
                            })
                            ->StoreOrder()->NotRefunded()
                            ->whereMonth('schedule_at', $i)->whereYear('schedule_at', date('Y') - 1)
                            ->sum('order_amount');
                    }
                    $label = $months;
                    $data = $monthly_order;
                    break;
                case "this_week":
                    $weekStartDate = now()->startOfWeek();
                    for ($i = 1; $i <= 7; $i++) {
                        $monthly_order[$i] = Order::when(isset($zone), function ($query) use ($zone) {
                            return $query->whereIn('store_id', $zone->stores->pluck('id'));
                        })
                            ->when(isset($store), function ($query) use ($store) {
                                return $query->where('store_id', $store->id);
                            })->StoreOrder()->NotRefunded()->whereDay('schedule_at', $weekStartDate->format('d'))->whereMonth('schedule_at', now()->format('m'))
                            ->sum('order_amount');
                        $weekStartDate = $weekStartDate->addDays(1);
                    }
                    $label = $days;
                    $data = $monthly_order;
                    break;
                case "this_month":
                    $start = now()->startOfMonth();
                    $end = now()->startOfMonth()->addDays(7);
                    $total_day = now()->daysInMonth;
                    $remaining_days = now()->daysInMonth - 28;
                    $weeks = array(
                        '"Day 1-7"',
                        '"Day 8-14"',
                        '"Day 15-21"',
                        '"Day 22-' . $total_day . '"',
                    );
                    for ($i = 1; $i <= 4; $i++) {
                        $monthly_order[$i] = Order::when(isset($zone), function ($query) use ($zone) {
                            return $query->whereIn('store_id', $zone->stores->pluck('id'));
                        })
                            ->when(isset($store), function ($query) use ($store) {
                                return $query->where('store_id', $store->id);
                            })
                            ->StoreOrder()->NotRefunded()
                            ->whereBetween('schedule_at', ["{$start->format('Y-m-d')} 00:00:00", "{$end->format('Y-m-d')} 23:59:59"])
                            ->sum('order_amount');
                        $start = $start->addDays(7);
                        $end = $i == 3 ? $end->addDays(7 + $remaining_days) : $end->addDays(7);
                    }
                    $label = $weeks;
                    $data = $monthly_order;
                    break;
                default:
                    for ($i = 1; $i <= 12; $i++) {
                        $monthly_order[$i] = Order::when(isset($zone), function ($query) use ($zone) {
                            return $query->whereIn('store_id', $zone->stores->pluck('id'));
                        })
                            ->when(isset($store), function ($query) use ($store) {
                                return $query->where('store_id', $store->id);
                            })->StoreOrder()->NotRefunded()->whereMonth('schedule_at', $i)->whereYear('schedule_at', now()->format('Y'))
                            ->sum('order_amount');
                    }
                    $label = $months;
                    $data = $monthly_order;
            }
        } else {

            $to = Carbon::parse($to);
            $from = Carbon::parse($from);

            $years_count = $to->diffInYears($from);
            $months_count = $to->diffInMonths($from);
            $weeks_count = $to->diffInWeeks($from);
            $days_count = $to->diffInDays($from);

            // dd($days_count);


            if ($years_count > 0) {
                $monthly_order = Order::when(isset($zone), function ($query) use ($zone) {
                    return $query->whereIn('store_id', $zone->stores->pluck('id'));
                })
                    ->when(isset($store), function ($query) use ($store) {
                        return $query->where('store_id', $store->id);
                    })
                    ->StoreOrder()->NotRefunded()
                    ->whereBetween('schedule_at', ["{$from}", "{$to->format('Y-m-d')} 23:59:59"])
                    ->select(
                        DB::raw("(sum(order_amount)) as order_amount"),
                        DB::raw("(DATE_FORMAT(schedule_at, '%Y')) as year")
                    )
                    ->groupBy('year')
                    ->get()->toArray();

                $label = array_map(function ($order) {
                    return $order['year'];
                }, $monthly_order);
                $data = array_map(function ($order) {
                    return $order['order_amount'];
                }, $monthly_order);
            } elseif ($months_count > 0) {
                for ($i = (int)$from->format('m'); $i <= (int)$from->format('m') + $months_count; $i++) {
                    $monthly_order[$i] = Order::when(isset($zone), function ($query) use ($zone) {
                        return $query->whereIn('store_id', $zone->stores->pluck('id'));
                    })
                        ->when(isset($store), function ($query) use ($store) {
                            return $query->where('store_id', $store->id);
                        })
                        ->StoreOrder()->NotRefunded()
                        ->whereMonth('schedule_at', $i)
                        ->sum('order_amount');
                    $label[$i] = $months[$i - 1];
                }
                $label = $label;
                $data = $monthly_order;
            } elseif ($weeks_count > 0) {
                // $start = $from;
                // $end = $from->addDays(7);
                // $weeks = [];
                // for ($i = 1; $i <= 4; $i++) {
                //     $weeks[$i] = '"Day ' . (int)$start->format('d') . '-' . ((int)$start->format('d') + 7) . '"';
                //     $monthly_order[$i] = Order::when(isset($zone), function ($query) use ($zone) {
                //         return $query->whereIn('store_id', $zone->stores->pluck('id'));
                //     })
                //         ->when(isset($store), function ($query) use ($store) {
                //             return $query->where('store_id', $store->id);
                //         })
                //         ->whereBetween('schedule_at', [$start, "{$end->format('Y-m-d')} 23:59:59"])
                //         ->sum('order_amount');

                //     $start = $end;
                //     $end = $start->addDays(7);
                // }
                // $label = $weeks;
                // $data = $monthly_order;
                for ($i = (int)$from->format('d'); $i <= (int)$to->format('d'); $i++) {
                    $monthly_order[$i] = Order::when(isset($zone), function ($query) use ($zone) {
                        return $query->whereIn('store_id', $zone->stores->pluck('id'));
                    })
                        ->when(isset($store), function ($query) use ($store) {
                            return $query->where('store_id', $store->id);
                        })
                        ->StoreOrder()->NotRefunded()
                        ->whereDay('schedule_at', $i)->whereMonth('schedule_at', $from->format('m'))->whereYear('schedule_at', $from->format('Y'))
                        ->sum('order_amount');
                    $label[$i] = $i;
                }
                $label = $label;
                $data = $monthly_order;
            } elseif ($days_count >= 0) {
                for ($i = (int)$from->format('d'); $i <= (int)$to->format('d'); $i++) {
                    $monthly_order[$i] = Order::when(isset($zone), function ($query) use ($zone) {
                        return $query->whereIn('store_id', $zone->stores->pluck('id'));
                    })
                        ->when(isset($store), function ($query) use ($store) {
                            return $query->where('store_id', $store->id);
                        })
                        ->StoreOrder()->NotRefunded()
                        ->whereDay('schedule_at', $i)->whereMonth('schedule_at', $from->format('m'))->whereYear('schedule_at', $from->format('Y'))
                        ->sum('order_amount');
                    $label[$i] = $i;
                }
                $label = $label;
                $data = $monthly_order;
            }
        }


        return view('admin-views.report.store-order-report', compact('zone', 'store', 'orders', 'orders_list', 'monthly_order', 'total_order_amount', 'order_payment_methods', 'total_coupon_discount', 'total_product_discount', 'label', 'data', 'filter', 'total_ongoing', 'total_canceled', 'total_delivered', 'total_ongoing_count', 'total_canceled_count', 'total_delivered_count'));
    }

    public function store_order_search(Request $request)
    {
        $key = explode(' ', $request['search']);

        $from = session('from_date');
        $to = session('to_date');

        $zone_id = $request->query('zone_id', isset(auth('admin')->user()->zone_id) ? auth('admin')->user()->zone_id : 'all');
        $store_id = $request->query('store_id', 'all');
        $zone = is_numeric($zone_id) ? Zone::findOrFail($zone_id) : null;
        $store = is_numeric($store_id) ? Store::findOrFail($store_id) : null;

        $orders = Order::with(['customer', 'store'])
            ->when(isset($zone), function ($query) use ($zone) {
                return $query->whereIn('store_id', $zone->stores->pluck('id'));
            })
            ->when(isset($store), function ($query) use ($store) {
                return $query->where('store_id', $store->id);
            })
            ->when(isset($from) && isset($to) && $from != null && $to != null, function ($query) use ($from, $to) {
                return $query->whereBetween('created_at', [$from . " 00:00:00", $to . " 23:59:59"]);
            })
            ->where(function ($q) use ($key) {
                foreach ($key as $value) {
                    $q->orWhere('id', 'like', "%{$value}%");
                }
            })
            ->withSum('transaction', 'admin_commission')
            ->withSum('transaction', 'admin_expense')
            ->withSum('transaction', 'delivery_fee_comission')
            ->StoreOrder()->NotRefunded()
            ->orderBy('schedule_at', 'desc')
            ->limit(25)->get();

        return response()->json([
            'count' => count($orders),
            'view' => view('admin-views.report.partials._store_order_table', compact('orders'))->render()
        ]);
    }

    public function store_order_export(Request $request)
    {
        $key = isset($request['search']) ? explode(' ', $request['search']) : [];

        $from = session('from_date');
        $to = session('to_date');

        $zone_id = $request->query('zone_id', isset(auth('admin')->user()->zone_id) ? auth('admin')->user()->zone_id : 'all');
        $store_id = $request->query('store_id', 'all');
        $zone = is_numeric($zone_id) ? Zone::findOrFail($zone_id) : null;
        $store = is_numeric($store_id) ? Store::findOrFail($store_id) : null;

        $orders = Order::with(['customer', 'store'])
            ->when(isset($zone), function ($query) use ($zone) {
                return $query->whereIn('store_id', $zone->stores->pluck('id'));
            })
            ->when(isset($store), function ($query) use ($store) {
                return $query->where('store_id', $store->id);
            })
            ->when(isset($from) && isset($to) && $from != null && $to != null, function ($query) use ($from, $to) {
                return $query->whereBetween('schedule_at', [$from . " 00:00:00", $to . " 23:59:59"]);
            })
            ->where(function ($q) use ($key) {
                foreach ($key as $value) {
                    $q->orWhere('id', 'like', "%{$value}%");
                }
            })
            ->withSum('transaction', 'admin_commission')
            ->withSum('transaction', 'admin_expense')
            ->withSum('transaction', 'delivery_fee_comission')
            ->StoreOrder()
            ->orderBy('schedule_at', 'desc')
            ->get();

        if ($request->type == 'excel') {
            return (new FastExcel(OrderLogic::format_store_order_export_data($orders)))->download('Orders.xlsx');
        } elseif ($request->type == 'csv') {
            return (new FastExcel(OrderLogic::format_store_order_export_data($orders)))->download('Orders.csv');
        }
    }

    public function store_sales_export(Request $request)
    {
        $key = isset($request['search']) ? explode(' ', $request['search']) : [];

        $from = session('from_date');
        $to = session('to_date');

        $zone_id = $request->query('zone_id', isset(auth('admin')->user()->zone_id) ? auth('admin')->user()->zone_id : 'all');
        $store_id = $request->query('store_id', 'all');
        $zone = is_numeric($zone_id) ? Zone::findOrFail($zone_id) : null;
        $store = is_numeric($store_id) ? Store::findOrFail($store_id) : null;
        $items = \App\Models\Item::withoutGlobalScope(StoreScope::class)->with('orders')->withCount([
            'orders' => function ($query) use ($from, $to) {
                $query->whereBetween('schedule_at', [$from . ' 00:00:00', $to . ' 23:59:29']);
            },
        ])
            ->when(isset($zone), function ($query) use ($zone) {
                return $query->whereIn('store_id', $zone->stores->pluck('id'));
            })
            ->when(isset($store), function ($query) use ($store) {
                return $query->where('store_id', $store->id);
            })->where(function ($q) use ($key) {
                foreach ($key as $value) {
                    $q->orWhere('name', 'like', "%{$value}%");
                }
            })
            ->get();

        if ($request->type == 'excel') {
            return (new FastExcel(StoreLogic::format_store_sales_export_data($items)))->download('items.xlsx');
        } elseif ($request->type == 'csv') {
            return (new FastExcel(StoreLogic::format_store_sales_export_data($items)))->download('items.csv');
        }
    }

    public function store_summary_export(Request $request)
    {
        $key = isset($request['search']) ? explode(' ', $request['search']) : [];

        $filter = $request->query('filter', 'all_time');

        $stores = Store::with('orders')
            ->when(isset($filter) && $filter == 'this_year', function ($query) {
                return $query->with([
                    'orders' => function ($query) {
                        $query->whereYear('schedule_at', now()->format('Y'));
                    },
                ]);
            })
            ->when(isset($filter) && $filter == 'this_month', function ($query) {
                return $query->with([
                    'orders' => function ($query) {
                        $query->whereMonth('schedule_at', now()->format('m'))->whereYear('schedule_at', now()->format('Y'));
                    },
                ]);
            })
            ->when(isset($filter) && $filter == 'previous_year', function ($query) {
                return $query->with([
                    'orders' => function ($query) {
                        $query->whereYear('schedule_at', date('Y') - 1);
                    },
                ]);
            })
            ->when(isset($filter) && $filter == 'this_week', function ($query) {
                return $query->with([
                    'orders' => function ($query) {
                        $query->whereBetween('schedule_at', [now()->startOfWeek()->format('Y-m-d H:i:s'), now()->endOfWeek()->format('Y-m-d H:i:s')]);
                    },
                ]);
            })
            // ->when(isset($filter) && $filter == 'this_year', function ($query) {
            //     return $query->whereYear('created_at', now()->format('Y'));
            // })
            //     ->when(isset($filter) && $filter == 'this_month', function ($query) {
            //         return $query->whereMonth('created_at', now()->format('m'))->whereYear('created_at', now()->format('Y'));
            //     })
            //     ->when(isset($filter) && $filter == 'this_month', function ($query) {
            //         return $query->whereMonth('created_at', now()->format('m'))->whereYear('created_at', now()->format('Y'));
            //     })
            //     ->when(isset($filter) && $filter == 'previous_year', function ($query) {
            //         return $query->whereYear('created_at', date('Y') - 1);
            //     })
            //     ->when(isset($filter) && $filter == 'this_week', function ($query) {
            //         return $query->whereBetween('created_at', [now()->startOfWeek()->format('Y-m-d H:i:s'), now()->endOfWeek()->format('Y-m-d H:i:s')]);
            //     })
            ->where(function ($q) use ($key) {
                foreach ($key as $value) {
                    $q->orWhere('name', 'like', "%{$value}%");
                }
            })
            ->Active()->orderBy('order_count', 'DESC')->get();

        if ($request->type == 'excel') {
            return (new FastExcel(StoreLogic::format_store_summary_export_data($stores)))->download('stores.xlsx');
        } elseif ($request->type == 'csv') {
            return (new FastExcel(StoreLogic::format_store_summary_export_data($stores)))->download('stores.csv');
        }
    }


    public function expense_report(Request $request)
    {
        if (session()->has('from_date') == false) {
            session()->put('from_date', now()->firstOfMonth()->format('Y-m-d'));
            session()->put('to_date', now()->lastOfMonth()->format('Y-m-d'));
        }
        $from = session('from_date');
        $to = session('to_date');

        $expense = Expense::whereBetween('created_at', [$from . ' 00:00:00', $to . ' 23:59:29'])
            ->paginate(config('default_pagination'))->withQueryString();

        return view('admin-views.report.expense-report', compact('expense'));
    }
    public function expense_export(Request $request)
    {
        if (session()->has('from_date') == false) {
            session()->put('from_date', now()->firstOfMonth()->format('Y-m-d'));
            session()->put('to_date', now()->lastOfMonth()->format('Y-m-d'));
        }
        $from = session('from_date');
        $to = session('to_date');

        $expense = Expense::whereBetween('created_at', [$from . ' 00:00:00', $to . ' 23:59:29'])
            ->get();

        if ($request->type == 'excel') {
            return (new FastExcel($expense))->download('ExpenseReport.xlsx');
        } elseif ($request->type == 'csv') {
            return (new FastExcel($expense))->download('ExpenseReport.csv');
        }
    }

    public function expense_search(Request $request)
    {
        $key = explode(' ', $request['search']);

        $from = session('from_date');
        $to = session('to_date');

        $expense = Expense::whereBetween('created_at', [$from . ' 00:00:00', $to . ' 23:59:29'])
            ->where(function ($q) use ($key) {
                foreach ($key as $value) {
                    $q->orWhere('type', 'like', "%{$value}%")->orWhere('description', 'like', "%{$value}%");
                }
            })
            ->limit(25)->get();

        return response()->json([
            'count' => count($expense),
            'view' => view('admin-views.report.partials._expense_table', compact('expense'))->render()
        ]);
    }
    public function order_mis_report(Request $request)
    {

        $orders = Order::with([
            'customer', 'store.vendor',
            'transaction', 'delivery_man'
        ])
            ->withSum('details', 'price')
            ->delivered()
            ->notPos()
            ->when(($request->from && $request->to), function ($query) use ($request) {
                $query->whereBetween('created_at', [$request->from . ' 00:00:00', $request->to . ' 23:59:59']);
            })
            ->orderBy('schedule_at', 'desc')
            ->paginate(config('default_pagination'));
        // dd($orders->items());
        return view('admin-views.report.order-mis', compact('orders'));
    }
    public function order_mis_export(Request $request)
    {
        $orders = Order::with(['customer', 'store', 'transaction'])->delivered()
            ->when(($request->from && $request->to), function ($query) use ($request) {
                $query->whereBetween('created_at', [$request->from . ' 00:00:00', $request->to . ' 23:59:59']);
            })
            ->withSum('details', 'price')
            ->delivered()
            ->notPos()
            ->orderBy('schedule_at', 'desc')
            ->get();
        return (new FastExcel(OrderLogic::gen_mis_report($orders)))->download('Orders_MIS_' . date('d_M_Y-h-i-s-A') . '.xlsx');
    }
}
