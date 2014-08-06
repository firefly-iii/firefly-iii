@extends('layouts.default')
@section('content')
<div class="row">
    <div class="col-lg-12 col-md-12 col-sm-12">
        <h1>Firefly
            <small>Recurring transactions</small>
        </h1>
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
                <th>Automatch</th>
                <th>Active</th>
            </tr>
        </table>
    </div>
</div>
@stop