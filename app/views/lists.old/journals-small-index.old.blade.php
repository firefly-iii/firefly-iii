<div class="list-group">
@foreach($transactions as $journal)
<a class="list-group-item" title="{{$journal->date->format('jS M Y')}}" href="{{route('transactions.show',$journal->id)}}">

    @if($journal->transactiontype->type == 'Withdrawal')
    <i class="fa fa-long-arrow-left fa-fw" title="Withdrawal"></i>
    @endif
    @if($journal->transactiontype->type == 'Deposit')
    <i class="fa fa-long-arrow-right fa-fw" title="Deposit"></i>
    @endif
    @if($journal->transactiontype->type == 'Transfer')
        <i class="fa fa-arrows-h fa-fw" title="Transfer"></i>
    @endif

    {{{$journal->description}}}

<span class="pull-right small">
    @foreach($journal->transactions as $t)
        @if($t->account_id == $account->id)
            {{mf($t->amount)}}
        @endif
    @endforeach
</span>

</a>
@endforeach
</div>