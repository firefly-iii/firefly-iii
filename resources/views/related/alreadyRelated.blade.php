@if($journals->count() > 0)
<table class="table table-bordered table-striped table-condensed">
    @foreach($journals as $journal)
        <tr>
            <td><a title="Unlink" data-id="{{$journal->id}}" data-parent="{{$parent->id}}" class="btn unrelate btn-xs btn-default" href="#"><span class="glyphicon glyphicon-resize-full"></span></a></td>
            <td>
                @if($journal->transactiontype->type == 'Withdrawal')
                    <i class="fa fa-long-arrow-left fa-fw" title="Withdrawal"></i>
                @endif
                @if($journal->transactiontype->type == 'Deposit')
                    <i class="fa fa-long-arrow-right fa-fw" title="Deposit"></i>
                @endif
                @if($journal->transactiontype->type == 'Transfer')
                    <i class="fa fa-arrows-h fa-fw" title="Transfer"></i>
                @endif
            </td>
            <td>{{$journal->date->format('jS M Y')}}</td>
            <td>
                <a title="{{$journal->date->format('jS M Y')}}" href="{{route('transactions.show',$journal->id)}}">{{{$journal->description}}}</a>
            </td>
            <td>
                @if($journal->transactiontype->type == 'Withdrawal')
                    <span class="text-danger">{{Amount::formatTransaction($journal->transactions[0],false)}}</span>
                @endif
                @if($journal->transactiontype->type == 'Deposit')
                    <span class="text-success">{{Amount::formatTransaction($journal->transactions[1],false)}}</span>
                @endif
                @if($journal->transactiontype->type == 'Transfer')
                    <span class="text-info">{{Amount::formatTransaction($journal->transactions[1],false)}}</span>
                @endif
            </td>

        </tr>
{{--





            <span class="pull-right small">
@if(isset($account))
                    @foreach($journal->transactions as $index => $t)
                        @if($t->account_id == $account->id)
                            {!! Amount::formatTransaction($t) !!}
                        @endif
                    @endforeach
                @else
                    @foreach($journal->transactions as $index => $t)
                        @if($index == 0)
                            {!! Amount::formatTransaction($t) !!}
                        @endif
                    @endforeach
                @endif
</span>

        </a>
        --}}
    @endforeach
</table>
@else
    <p><em>No related transactions</em></p>
@endif