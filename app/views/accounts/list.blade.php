<table class="table table-bordered table-striped">
    <tr>
        <th style="width:25px;">&nbsp;</th>
        <th style="width:30%;">Name</th>
        <th>Current balance</th>
    </tr>
    @foreach($accounts as $account)
    <tr>
        <td>
            @if($account->active == 0)
                <span title="This account is inactive." class="glyphicon glyphicon-ban-circle"></span>
            @endif
        </td>
        <td>
            <a href="{{route('accounts.show',$account->id)}}" title="Overview for account {{{$account->name}}}">{{{$account->name}}}</a></td>
        <td>{{mf($account->balance())}}</td>
    </tr>
    @endforeach
</table>