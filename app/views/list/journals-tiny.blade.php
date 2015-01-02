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
@if(isset($account))
    @foreach($journal->transactions as $index => $t)
        @if($t->account_id == $account->id)
            {{mft($t)}}
        @endif
    @endforeach
@else
    @foreach($journal->transactions as $index => $t)
        @if($index == 0)
                {{mft($t)}}
        @endif
    @endforeach
@endif
</span>

</a>
@endforeach
</div>
