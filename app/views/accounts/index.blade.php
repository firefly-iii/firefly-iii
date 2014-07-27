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
    </div>
</div>
<div class="row">
    <div class="col-lg-12 col-md-12 col-sm-12">
            <h3>Your accounts</h3>
            <p style="width:50%;" class="text-info">
                These are your personal accounts.
            </p>

            @include('accounts.list',['accounts' => $accounts['personal']])

            <h3>Beneficiaries</h3>
            <p style="width:50%;" class="text-info">
                These are beneficiaries; places where you spend money or people who pay you.
            </p>

            @include('accounts.list',['accounts' => $accounts['beneficiaries']])

            <h3>Initial balances</h3>
            <p style="width:50%;" class="text-info">
                These are system accounts; created to add balance to the books when you add a personal account
                which already has money in it. That money has to come from somewhere.
            </p>
            @include('accounts.list',['accounts' => $accounts['initial']])

            <h3>Cash</h3>
            <p style="width:50%;" class="text-info">
                This is a system account. When you don't specify a beneficiary or draw many from an ATM (or put cash in your
                personal accounts) it gets added or drawn from this account.
            </p>
        @include('accounts.list',['accounts' => $accounts['cash']])
    </div>
</div>

@stop