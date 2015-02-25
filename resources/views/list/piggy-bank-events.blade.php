<table class="table table-bordered table-striped">
    <tr>
        @if(isset($showPiggyBank) && $showPiggyBank === true)
            <th>Piggy bank</th>
        @endif
        <th>Date</th>
        <th>Amount</th>
    </tr>
    @foreach($events as $event)
    <tr>
        @if(isset($showPiggyBank) && $showPiggyBank === true)
        <td>
            <a href="{{route('piggyBanks.show',$event->piggyBank_id)}}">{{{$event->piggyBank->name}}}</a>
        </td>
        @endif
        <td>
            @if(!is_null($event->transaction_journal_id))
                <a href="{{route('transactions.show',$event->transaction_journal_id)}}" title="{{{$event->transactionJournal->description}}}">{{$event->date->format('j F Y')}}</a>
            @else
                {{$event->date->format('j F Y')}}
            @endif
            </td>

        <td>
            @if($event->amount < 0)
                <span class="text-danger">Removed {{Amount::format($event->amount*-1,false)}}</span>
            @else
                <span class="text-success">Added {{Amount::format($event->amount,false)}}</span>
            @endif
            </td>
    </tr>
    @endforeach
</table>
