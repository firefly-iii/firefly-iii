<table class="table table-bordered table-striped">
    <tr>
        <th>&nbsp;</th>
        <th style="width:30%;">Name</th>
        <th>Current balance</th>
        <th></th>
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
        <td>
            <span class="btn-group-xs btn-group">
                <a href="{{route('accounts.edit',$account->id)}}" title="Edit {{{$account->name}}}" class="btn btn-default"><span class="glyphicon glyphicon-pencil"></span></a>
                <a href="{{route('accounts.delete',$account->id)}}" title="Edit {{{$account->name}}}" class="btn btn-danger"><span class="glyphicon glyphicon-trash"></span></a>
            </span>
        </td>
    </tr>
    @endforeach
</table><!-- TODO remove me -->