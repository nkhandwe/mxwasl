@foreach ($order_transactions as $k => $ot)
    <tr scope="row">
        <td>{{ $k + 1 }}</td>
        <td><a href="{{route('admin.order.details',$ot->order_id)}}">{{$ot->order_id}}</a></td>
        <td>{{\App\CentralLogics\Helpers::format_currency($ot->order_amount)}}</td>
        <td>{{\App\CentralLogics\Helpers::format_currency($ot->store_amount - $ot->tax)}}</td>
        <td>{{\App\CentralLogics\Helpers::format_currency($ot->admin_commission)}}</td>
        <td>{{\App\CentralLogics\Helpers::format_currency($ot->delivery_charge)}}</td>
        <td>{{\App\CentralLogics\Helpers::format_currency($ot->delivery_fee_comission)}}</td>
        <td>{{\App\CentralLogics\Helpers::format_currency($ot->tax)}}</td>
        <td class="text-capitalize">{{ $ot->received_by }}</td>
        <td>{{ $ot->created_at->format('Y/m/d ' . config('timeformat')) }}</td>
    </tr>
@endforeach
