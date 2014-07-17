<table class="table table-condensed">
    <tr>
        <th>Total</th>
        <th>{{mf($sum*-1)}}</th>
    </tr>
    @foreach($rows as $name => $amount)
    <tr>
        <td><a href="#">{{{$name}}}</a></td>
        <td>{{mf($amount*-1)}}</td>
    </tr>
    @endforeach
</table>