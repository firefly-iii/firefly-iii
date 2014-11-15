<table class="table table-bordered table-striped">
    <tr>
        <th>Date</th>
        <th>Amount</th>
    </tr>
    @foreach($events as $event)
    <tr>
        <td>{{$event->date->format('j F Y')}}</td>
        <td>
            @if($event->amount < 0)
                <span class="text-danger">Removed {{mf($event->amount*-1,false)}}</span>
            @else
                <span class="text-success">Added {{mf($event->amount,false)}}</span>
            @endif
            </td>
    </tr>
    @endforeach
</table>