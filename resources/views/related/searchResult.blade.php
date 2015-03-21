<!-- search for: {{$search}} with {{$journals->count()}} results -->
@if($journals->count() > 0)
<table class="table table-bordered table-striped table-condensed">
    @foreach($journals as $journal)
        <tr>
            <td><a title="Link" data-id="{{$journal->id}}" data-parent="{{$parent->id}}" class="btn relate btn-xs btn-default" href="#"><i class="fa fa-fw fa-expand"></i></a></td>
            <td>
                @if($journal->transactiontype->type == 'Withdrawal')
                    <i class="fa fa-long-arrow-left fa-fw" title="Withdrawal"></i>
                @endif
                @if($journal->transactiontype->type == 'Deposit')
                    <i class="fa fa-long-arrow-right fa-fw" title="Deposit"></i>
                @endif
                @if($journal->transactiontype->type == 'Transfer')
                    <i class="fa fa-fw fa-exchange" title="Transfer"></i>
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
    @endforeach
</table>
@else
    <p><em>No results</em></p>
@endif