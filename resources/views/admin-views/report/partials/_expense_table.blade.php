@foreach($expense as $k=>$exp)
<tr>
    <td scope="row">{{$k+1}}</td>
    <td><label class="text-uppercase">{{translate("messages.{$exp['type']}")}}</label></td>
    <td><div class="pl-4">
        {{$exp['amount']}}
    </div></td>
    <td><div class="pl-4">
        {{$exp['description']}}
    </div></td>
    <td>{{$exp->created_at->format('Y-m-d '.config('timeformat'))}}</td>
    {{-- <td>
        <div class="btn--container justify-content-center">
            <a href="{{route('admin.expense.show',[$exp['id']])}}"
            class="btn action-btn btn--warning btn-outline-warning"><i class="tio-visible"></i>
            </a>
        </div>
    </td> --}}
</tr>
@endforeach
