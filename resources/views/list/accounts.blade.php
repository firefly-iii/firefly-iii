<table class="table table-striped table-bordered sortable">
    <thead>
    <tr>
        <th data-defaultsort="disabled">&nbsp;</th>
        <th>Name</th>
        @if(isset($what) && $what == 'asset')
        <th>Role</th>
        @endif
        <th>Current balance</th>
        <th>Active</th>
        <th data-dateformat="D MMMM YYYY">Last activity</th>
        <th>Balance difference between {{Session::get('start')->format('jS F Y')}} and {{Session::get('end')->format('jS F Y')}}</th>
    </tr>
    </thead>
    <tbody>
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
        <?php $balance = Steam::balance($account);?>
        <td data-value="{{$balance}}">{!! Amount::format($balance) !!}</td>
        <td data-value="{{intval($account->active)}}">
            @if($account->active)
                <i class="fa fa-fw fa-check"></i>
            @else
                <i class="fa fa-fw fa-ban"></i>
            @endif
        </td>
            @if($account->lastActivityDate)
                <td>
                    {{{$account->lastActivityDate->format('j F Y')}}}
                </td>
            @else
                <td data-value="0000-00-00">
                    <em>Never</em>
                </td>
            @endif
        <td data-value="{{$account->endBalance - $account->startBalance}}">
            {!! Amount::format($account->endBalance - $account->startBalance) !!}
        </td>

    </tr>

    @endforeach
    </tbody>
</table>