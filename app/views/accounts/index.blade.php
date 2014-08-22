@extends('layouts.default')
@section('content')
<div class="row">
    <div class="col-lg-12 col-md-12 col-sm-12">
        <h1>Firefly
            <small>Accounts</small>
        </h1>
        <p class="lead">
            Accounts are the record holders for transactions and transfers. Money moves from one account to another.
        </p>
        <p class="text-info">
            In a double-entry bookkeeping system almost <em>everything</em> is an account. Your own personal
            bank accounts are representated as accounts (naturally), but the stores you buy stuff at are also
            represented as accounts. Likewise, if you have a job, your salary is drawn from their account.
        </p>
        <p>
            <a href="{{route('accounts.create')}}" class="btn btn-success">Create a new account</a>
        </p>
    </div>
</div>
<div class="row">
    <div class="col-lg-12 col-md-12 col-sm-12">
            @if(count($accounts['personal']) > 0)
            <h3>Your accounts</h3>
            <p style="width:50%;" class="text-info">
                These are your personal accounts.
            </p>

            @include('accounts.list',['accounts' => $accounts['personal']])
            @endif

            @if(count($accounts['beneficiaries']) > 0)
            <h3>Beneficiaries</h3>
            <p style="width:50%;" class="text-info">
                These are beneficiaries; places where you spend money or people who pay you.
            </p>
            @include('accounts.list',['accounts' => $accounts['beneficiaries']])
            @endif

    </div>
</div>

@stop