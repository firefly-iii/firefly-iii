@extends('layouts.default')
@section('content')
<div class="row">
    <div class="col-lg-12 col-md-12 col-sm-12">
        <h1>Firefly
            <small>Piggy banks</small>
        </h1>
        <p class="lead">Set targets and save money</p>
        <p class="text-info">
            Saving money is <em>hard</em>. Piggy banks allow you to group money
            from an account together. If you also set a target (and a target date) you
            can save towards your goals.
        </p>
        @if($count == 0)
        <p>
            <a href="{{route('piggybanks.create')}}" class="btn btn-success">Create new piggy bank</a>
        </p>
        @endif
    </div>
</div>

@if($count > 0)
<div class="row">
    <div class="col-lg-6 col-md-6 col-sm-12">
        <h4>Accounts used for piggy banks</h4>
        <table class="table table-striped">
            <tr>
                <th>Account</th>
                <th>Current balance</th>
                <th>Left for (other) piggy banks</th>
            </tr>
            @foreach($accounts as $account)
            <tr>
                <td>{{{$account->name}}}</td>
                <td>{{mf($account->balance())}}</td>
                <td>{{mf($account->left)}}</td>
            </tr>
            @endforeach
        </table>
    </div>
</div>

<div class="row">
    <div class="col-lg-12 col-md-6 col-sm-12">
        <h3>Piggy banks</h3>

        @foreach($piggybanks as $piggybank)
        <h4>{{{$piggybank->name}}} <small>{{mf($piggybank->target)}}</small></h4>
        <table class="table table-bordered">
            <tr>
                <td style="width:10%;"><span id="piggy_{{$piggybank->id}}_amount">{{mf($piggybank->amount,false)}}</span></td>
                <td><input type="range" name="piggy_{{$piggybank->id}}" min="1" max="{{$piggybank->target}}" step="any" value="{{$piggybank->amount}}" /></td>
                <td>Y</td>
            </tr>
        </table>
        @endforeach


        <p>
            <a href="{{route('piggybanks.create')}}" class="btn btn-success">Create new piggy bank</a>
        </p>

        </div>
    </div>

@endif
@stop
@section('scripts')
<?php echo javascript_include_tag('piggybanks'); ?>
@stop