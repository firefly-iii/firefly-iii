<table class="table table-condensed">
    <tr>
        <th>Total</th>
        <th>{{mf($sum*-1)}}</th>
    </tr>
    @foreach($rows as $name => $entry)
    <tr>
        <td><a href="{{route('accounts.show',$entry['id'])}}">{{{$name}}}</a></td>
        <td>{{mf($entry['amount']*-1)}}</td>
    </tr>
    @endforeach
</table>