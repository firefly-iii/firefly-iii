@extends('layouts.default')
@section('content')
<div class="row">
    <div class="col-lg-12 col-md-12 col-sm-12">
        <h1>Firefly
            <small>Piggy banks, large expenses and repeated expenses</small>
        </h1>
    </div>
</div>
<div class="row">
    <div class="col-lg-6 col-md-6 col-sm-12">
        <p class="lead">Save money for large expenses</p>
        <p class="text-info">
            Saving money is <em>hard</em>. Firefly's piggy banks can help you to save money. Simply set the amount
            of money you want to save, set an optional target and whether or not Firefly should remind you to add money
            to the piggy bank.
        </p>
        <p>
            <a href="{{route('piggybanks.create')}}" class="btn btn-success">Create new piggy bank</a>
        </p>
    </div>
    <div class="col-lg-6 col-md-6 col-sm-12">
        <p class="lead">Repeated expenses</p>
        <p class="text-info">
            Taxes are due every year. Or maybe you want to save up for your yearly fireworks-binge. Buy a new smart
            phone every three years. Firefly can help you organize these repeated expenses.
        </p>
        <p>
            <a href="{{route('piggybanks.create')}}" class="btn btn-success">Create new repeated expense</a>
        </p>
    </div>
</div>
{{--

        <p class="text-info">
            Saving money is <em>hard</em>. Firefly's piggy banks can help you to save money. You can do two things using
            these piggy banks:
        </p>
        <ol class="text-info">
            <li>Save money towards a singular goal such as a new bike or a new car.</li>
            <li>Save money repeatedly, for yearly expenses or long-term recurring investments. One example may be buying
            a new phone every three year.</li>
        </ol>
        @if($count == 0)
        <p>
            <a href="{{route('piggybanks.create')}}" class="btn btn-success">Create new piggy bank</a>
        </p>
        @endif
    </div>
</div>


@if($count > 0)
<div class="row">
    <div class="col-lg-12 col-md-12 col-sm-12">
        <h4>Accounts used for piggy banks</h4>
        <p class="text-info">
            @if(count($accounts) != 1)
            These (savings) accounts have
            @else
            This (savings) account has
            @endif
            @if(count($piggybanks) == 1)
            a piggy bank
            @else
            piggy banks
            @endif
            associated to
            @if(count($piggybanks) != 1)
            them.
            @else
            it.
            @endif
             If you transfer money to or from
            @if(count($accounts) != 1)
            these accounts,
            @else
            this account,
            @endif
            you may associate it with your @if(count($piggybanks) != 1)
            piggy banks.
            @else
            piggy bank.
            @endif
        </p>
    </div>
</div>
<div class="row">
    <div class="col-lg-6 col-md-6 col-sm-12">
        <table class="table table-striped">
            <tr>
                <th>Account</th>
                <th>Current balance</th>
                <th>Left for (other) piggy banks</th>
                <th>Total target</th>
            </tr>
            @foreach($accounts as $account)
            <tr>
                <td>{{{$account->name}}}</td>
                <td id="account_{{$account->id}}_total" data-raw="{{$account->balance}}">{{mf($account->balance)}}</td>
                <td id="account_{{$account->id}}_left" data-raw="{{$account->left}}">{{mf($account->left)}}</td>
                <td>{{mf($account->total)}}</td>
            </tr>
            @endforeach
        </table>
    </div>
</div>

<div class="row">
    <div class="col-lg-12 col-md-6 col-sm-12">
        <h3>Piggy banks</h3>

        @foreach($piggybanks as $piggybank)
        <h4>
            <a href="{{route('piggybanks.show',$piggybank->id)}}">{{{$piggybank->name}}}</a>
        </h4>
        @endforeach


        @foreach($piggybanks as $piggybank)

        @if(!is_null($piggybank->targetdate))
        <p>
            Target date: {{$piggybank->targetdate->format('jS F Y')}}
        </p>
        @endif
        <table class="table table-bordered">
            <tr>
                <td style="width:15%;">
                    <div class="input-group">
                        <span class="input-group-addon">&euro;</span>
                    <input class="form-control"  type="number" data-piggy="{{$piggybank->id}}" data-account="{{$piggybank->account_id}}" step="any" min="0" max="{{$piggybank->target}}" id="piggy_{{$piggybank->id}}_amount" value="{{$piggybank->amount}}" />
                        </div>
                </td>
                <td><input type="range" data-account="{{$piggybank->account_id}}" name="piggy_{{$piggybank->id}}" min="0" max="{{$piggybank->target}}" step="any" value="{{$piggybank->amount}}" /></td>
                <td style="width: 10%;"><span id="piggy_{{$piggybank->id}}_pct">{{$piggybank->pct}}</span></td>
                <td style="width:8%;">
                    <div class="btn-group btn-group-xs">
                        <a href="{{route('piggybanks.edit',$piggybank->id)}}" class="btn btn-default btn-xs"><span class="glyphicon glyphicon-pencil"></span></a>
                        <a href="{{route('piggybanks.delete',$piggybank->id)}}" class="btn btn-danger btn-xs"><span class="glyphicon glyphicon-trash"></span></a>
                    </div>
                </td>
            </tr>
        </table>
        @endforeach
        <p>
            <a href="{{route('piggybanks.create')}}" class="btn btn-success">Create new piggy bank</a>
        </p>

        </div>
    </div>
@endif
--}}

@stop
@section('scripts')
<script type="text/javascript">
var accountBalances = [];
var accountLeft = [];
@foreach($accounts as $account)
    accountBalances[{{$account->id}}] = {{$account->balance()}};
    accountLeft[{{$account->id}}] = {{$account->left}};
@endforeach
</script>

<?php echo javascript_include_tag('piggybanks'); ?>
@stop