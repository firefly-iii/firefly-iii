@extends('layouts.default')
@section('content')
{{ Breadcrumbs::renderIfExists(Route::getCurrentRoute()->getName(), $start) }}
@if(count($journals) == 0)
<div class="row">
    <div class="col-lg-4 col-md-4 col-sm-6">
        <p class="text-success">Everything accounted for.</p>
    </div>
</div>
@endif
@if(count($journals) > 0)
<div class="row">
    <div class="col-lg-4 col-md-4 col-sm-6">
        <h3>Withdrawals</h3>
    </div>
</div>

<div class="row">
    @foreach($journals as $journal)
        <div class="col-lg-4 col-md-4 col-sm-6">
            <div class="panel panel-default">
                <div class="panel-heading">
                    @if($journal->transactiontype->type == 'Withdrawal')
                        <span class="glyphicon glyphicon-arrow-left" title="Withdrawal"></span>
                    @endif
                    @if($journal->transactiontype->type == 'Deposit')
                        <span class="glyphicon glyphicon-arrow-right" title="Deposit"></span>
                    @endif
                    @if($journal->transactiontype->type == 'Transfer')
                        <span class="glyphicon glyphicon-resize-full" title="Transfer"></span>
                    @endif
                    @if($journal->transactiontype->type == 'Opening balance')
                        <span class="glyphicon glyphicon-ban-circle" title="Opening balance"></span>
                    @endif
                    <a href="{{route('transactions.show',$journal->id)}}">{{{$journal->description}}}</a>
                </div>
                <div class="panel-body">
                    <p>Spent {{mf($journal->getAmount())}}</p>
                    <p class="text-danger">No counter transaction!</p>
                    <p>
                        <a href="#"  data-id="{{{$journal->id}}}" class="relateTransaction btn btn-default">Add counter transaction</a>
                    </p>
                </div>
            </div>
        </div>
    @endforeach
</div>
@endif
@stop
@section('scripts')
<script type="text/javascript">
    var currencyCode = '{{getCurrencyCode()}}';
</script>
{{HTML::script('assets/javascript/firefly/reports.js')}}
{{HTML::script('assets/javascript/firefly/related-manager.js')}}
@stop