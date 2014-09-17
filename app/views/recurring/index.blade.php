@extends('layouts.default')
@section('content')
<div class="row">
    <div class="col-lg-12 col-md-12 col-sm-12">
        <p class="lead">Use recurring transactions to track repeated withdrawals</p>
        <p class="text-info">We all have bills to pay. Firefly can help you organize those bills into recurring transactions,
        which are exactly what the name suggests. Firefly can match new (and existing) transactions to such a recurring transaction
        and help you organize these expenses into manageable groups. The front page of Firefly will show you which recurring
        transactions you have missed, which are yet to come and which have been paid.</p>

    </div>
</div>

<div class="row">
    <div class="col-lg-12 col-sm-12 col-md-12">
        <table class="table table-striped">
            <tr>
                <th>Name</th>
                <th>Matches on</th>
                <th>Amount between</th>
                <th>Expected every</th>
                <th>Next expected match</th>
                <th>Auto-match</th>
                <th>Active</th>
                <th></th>
            </tr>
            @foreach($list as $entry)
            <tr>
                <td><a href="{{route('recurring.show',$entry->id)}}">{{{$entry->name}}}</a></td>
                <td>
                    @foreach(explode(' ',$entry->match) as $word)
                    <span class="label label-info">{{{$word}}}</span>
                    @endforeach
                </td>
                <td>
                    {{mf($entry->amount_min)}} &ndash;
                    {{mf($entry->amount_max)}}
                </td>
                <td>
                    {{$entry->repeat_freq}}
                </td>
                <td>
                    {{$entry->next()->format('d-m-Y')}}
                </td>
                <td>
                    @if($entry->automatch)
                        <span class="glyphicon glyphicon-ok"></span>
                    @else
                        <span class="glyphicon glyphicon-remove"></span>
                    @endif
                </td>
                <td>
                    @if($entry->active)
                    <span class="glyphicon glyphicon-ok"></span>
                    @else
                    <span class="glyphicon glyphicon-remove"></span>
                    @endif
                </td>
                <td>
                    <div class="btn-group btn-group-xs">
                        <a href="{{route('recurring.edit',$entry->id)}}" class="btn btn-default"><span class="glyphicon glyphicon-pencil"></span></a>
                        <a href="{{route('recurring.delete',$entry->id)}}" class="btn btn-danger"><span class="glyphicon glyphicon-trash"></span></a>
                    </div>
                </td>
            </tr>
            @endforeach
        </table>
        <p>
            <a href="{{route('recurring.create')}}" class="btn btn-success btn-large">Create new recurring transaction</a>
        </p>
    </div>
</div>
@stop