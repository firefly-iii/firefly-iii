@if(is_object($accounts) && method_exists($accounts, 'render'))
    {!! $accounts->render() !!}
@endif
<table class="table table-striped table-bordered">
    <tr>
        <th>&nbsp;</th>
        <th>Name</th>
        @if(isset($what) && $what == 'asset')
        <th>Role</th>
        @endif
        <th>Current balance</th>
        <th>Active</th>
        <th>Last activity</th>
        <th>Balance difference between {{Session::get('start')->format('jS F Y')}} and {{Session::get('end')->format('jS F Y')}}</th>
    </tr>
    @foreach($accounts as $account)
    <tr>
        <td>
            <div class="btn-group btn-group-xs">
                <a class="btn btn-default btn-xs" href="{{route('accounts.edit',$account->id)}}"><i class="fa fa-fw fa-pencil"></i></a>
                <a class="btn btn-danger btn-xs" href="{{route('accounts.delete',$account->id)}}"><i class="fa fa-fw fa-trash-o"></i></a>
            </div>
        </td>
        <td><a href="{{route('accounts.show',$account->id)}}">{{{$account->name}}}</a></td>
        @if(isset($what) && $what == 'asset')
        <td>
            @foreach($account->accountmeta as $entry)
                @if($entry->name == 'accountRole')
                    {{Config::get('firefly.accountRoles.'.$entry->data)}}
                @endif
            @endforeach
        </td>
        @endif
        <td>{!! Amount::format(Steam::balance($account)) !!}</td>
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
        <td>
            {!! Amount::format($account->endBalance - $account->startBalance) !!}
        </td>

    </tr>

    @endforeach
</table>
@if(is_object($accounts) && method_exists($accounts, 'render'))
    {!! $accounts->render() !!}
@endif
