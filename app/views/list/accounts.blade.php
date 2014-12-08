<table class="table table-striped">
    <tr>
        <th>&nbsp;</th>
        <th>Name</th>
        <th>Role</th>
        <th>Current balance</th>
        <th>Active</th>
        <th>Last activity</th>
    </tr>
    @foreach($accounts as $account)
    <tr>
        <td>
            <div class="btn-group btn-group-xs">
                <a class="btn btn-default btn-xs" href="{{route('accounts.edit',$account->id)}}"><span class="glyphicon glyphicon-pencil"></span></a>
                <a class="btn btn-danger btn-xs" href="{{route('accounts.delete',$account->id)}}"><span class="glyphicon glyphicon-trash"></span></a>
            </div>
        </td>
        <td><a href="{{route('accounts.show',$account->id)}}">{{{$account->name}}}</a></td>
        <td>{{{$account->accountRole}}}</td>
        <td>{{mf(Steam::balance($account))}}</td>
        <td>
            @if($account->active)
                <i class="fa fa-fw fa-check"></i>
            @else
                <i class="fa fa-fw fa-ban"></i>
            @endif
        </td>
        <td>
            @if($account->lastActivityDate)
                {{{$account->lastActivityDate->format('j F Y')}}}
            @else
                <em>Never</em>
            @endif
        </td>
    </tr>

    @endforeach
</table>