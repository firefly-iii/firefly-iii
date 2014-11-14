@extends('layouts.default')
@section('content')
@if(!is_null($query))
<div class="row"><!-- TODO cleanup for new forms and layout and see if it actually still works. -->
    @if(isset($result['transactions']) && $result['transactions']->count() > 0)
    <div class="col-lg-6 col-md-12 col-sm-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <i class="fa fa-repeat"></i> Transactions ({{$result['transactions']->count()}})
            </div>
            <div class="panel-body">
                @include('...lists.old.journals-small-noaccount',['transactions' => $result['transactions']])
            </div>
        </div>
    </div>
    @endif
    @if(isset($result['categories']) && $result['categories']->count() > 0)
    <div class="col-lg-6 col-md-12 col-sm-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <i class="fa fa-bar-chart"></i> Categories ({{$result['categories']->count()}})
            </div>
            <div class="panel-body">
                <div class="list-group">
                    @foreach($result['categories'] as $category)
                        <a class="list-group-item" title="{{$category->name}}" href="{{route('categories.show',$category->id)}}">
                            {{{$category->name}}}
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    @endif
    @if(isset($result['tags']) && $result['tags']->count() > 0)
    <div class="col-lg-6 col-md-12 col-sm-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <i class="fa fa-bar-chart"></i> Tags ({{$result['tags']->count()}})
            </div>
            <div class="panel-body">
                <p>Bla bla</p>
            </div>
        </div>
    </div>
    @endif
    @if(isset($result['accounts']) && $result['accounts']->count() > 0)
    <div class="col-lg-6 col-md-12 col-sm-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <i class="fa fa-credit-card"></i> Accounts ({{$result['accounts']->count()}})
            </div>
            <div class="panel-body">
                <div class="list-group">
                    @foreach($result['accounts'] as $account)
                        <a class="list-group-item" title="{{$account->name}}" href="{{route('accounts.show',$account->id)}}">
                            {{{$account->name}}}
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    @endif
    @if(isset($result['budgets']) && $result['budgets']->count() > 0)
    <div class="col-lg-6 col-md-12 col-sm-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <i class="fa fa-tasks"></i> Budgets ({{$result['budgets']->count()}})
            </div>
            <div class="panel-body">
                <div class="list-group">
                    @foreach($result['budgets'] as $budget)
                        <a class="list-group-item" title="{{$budget->name}}" href="{{route('budgets.show',$budget->id)}}">
                            {{{$budget->name}}}
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    @endif
    <!--
    <div class="col-lg-6 col-md-12 col-sm-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <i class="fa fa-search-plus"></i> Other results
            </div>
            <div class="panel-body">
                <p>Bla bla</p>
            </div>
        </div>
    </div>
    -->
</div>
@endif



@stop
@section('scripts')
<script type="text/javascript">
    var query = '{{{$query}}}';
</script>
@stop