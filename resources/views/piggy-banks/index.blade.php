@extends('layouts.default')
@section('content')
{!! Breadcrumbs::renderIfExists(Route::getCurrentRoute()->getName()) !!}
<div class="row">
    <div class="col-lg-12 col-md-12 col-sm-12">
        <p>
            <a href="{{route('piggy-banks.create')}}" class="btn btn-success">Create new piggy bank</a>
        </p>
    </div>
</div>
<div class="row">
@foreach($piggyBanks as $piggyBank)
    <div class="col-lg-3 col-md-4 col-sm-12 col-xs-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <i class="fa fa-fw fa-rocket"></i> <a href="{{route('piggy-banks.show',$piggyBank->id)}}" title="{{{$piggyBank->name}}}">{{{$piggyBank->name}}}</a>

                <!-- ACTIONS MENU -->
                <div class="pull-right">
                    <div class="btn-group">
                        <button type="button" class="btn btn-default btn-xs dropdown-toggle" data-toggle="dropdown">
                            Actions
                            <span class="caret"></span>
                        </button>
                        <ul class="dropdown-menu pull-right" role="menu">
                            <li><a href="{{route('piggy-banks.edit',$piggyBank->id)}}"><i class="fa fa-pencil fa-fw"></i> Edit</a></li>
                            <li><a href="{{route('piggy-banks.delete',$piggyBank->id)}}"><i class="fa fa-trash fa-fw"></i> Delete</a></li>
                        </ul>
                    </div>
                </div>

            </div>
            <div class="panel-body">
                <div class="row">
                    <!-- One block (remove money) -->
                    <div class="col-lg-2 col-md-2 col-sm-4 col-xs-4">
                        @if($piggyBank->savedSoFar > 0)
                            <a href="{{route('piggy-banks.removeMoney',$piggyBank->id)}}" class="btn btn-default btn-xs removeMoney" data-id="{{{$piggyBank->id}}}"><span data-id="{{{$piggyBank->id}}}" class="glyphicon glyphicon-minus"></span></a>
                        @endif
                    </div>
                    <!-- Some blocks (bar) -->
                    <div class="col-lg-8 col-md-8 col-sm-4 col-xs-4">
                        <div class="progress progress-striped">
                            <div
                            @if($piggyBank->percentage == 100)
                                class="progress-bar progress-bar-success"
                                @else
                                class="progress-bar progress-bar-info"
                                @endif
                                role="progressbar" aria-valuenow="{{$piggyBank->percentage}}" aria-valuemin="0" aria-valuemax="100" style="min-width: 40px;width: {{$piggyBank->percentage}}%;">
                                {{$piggyBank->percentage}}%
                            </div>
                        </div>
                    </div>


                    <!-- One block (add money) -->
                    <div class="col-lg-2 col-md-2 col-sm-4 col-xs-4">
                        @if($piggyBank->leftToSave > 0)
                            <a href="{{route('piggy-banks.addMoney',$piggyBank->id)}}" class="btn btn-default btn-xs addMoney" data-id="{{{$piggyBank->id}}}"><span data-id="{{{$piggyBank->id}}}" class="glyphicon glyphicon-plus"></span></a>
                        @endif

                    </div>

                </div>
                <div class="row">
                    <div class="col-lg-4 col-md-4 col-sm-4 col-xs-4">
                        <span title="Saved so far">{!! Amount::format($piggyBank->savedSoFar,true) !!}</span>
                    </div>
                    <div class="col-lg-4 col-md-4 col-sm-4 col-xs-4" style="text-align: center;">
                        <span title="Target amount">{!! Amount::format($piggyBank->targetamount,true) !!}</span>
                    </div>
                    <div class="col-lg-4 col-md-4 col-sm-4 col-xs-4" style="text-align: right;">
                        @if($piggyBank->leftToSave > 0)
                            <span title="Left to save">{!! Amount::format($piggyBank->leftToSave) !!}</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endforeach
</div>

<div class="row">
    <div class="col-lg-12 col-md-12 col-sm-12">
        <p>
            <a href="{{route('piggy-banks.create')}}" class="btn btn-success">Create new piggy bank</a>
        </p>
    </div>
</div>

<div class="row">
    <div class="col-lg-12 col-md-12 col-sm-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <i class="fa fa-fw fa-money"></i> Account status
            </div>
            <table class="table table-striped">
                <tr>
                    <th>Account</th>
                    <th>Balance</th>
                    <th>Left for piggy banks</th>
                    <th>Sum of piggy banks</th>
                    <th>Saved so far</th>
                    <th>Left to save</th>
                </tr>
                @foreach($accounts as $id => $info)
                    <tr>
                        <td><a href="{{route('accounts.show',$id)}}">{{{$info['name']}}}</a></td>
                        <td>{!! Amount::format($info['balance']) !!}</td>
                        <td>{!! Amount::format($info['leftForPiggyBanks']) !!}</td>
                        <td>{!! Amount::format($info['sumOfTargets']) !!}</td>
                        <td>{!! Amount::format($info['sumOfSaved']) !!}</td>
                        <td>{!! Amount::format($info['leftToSave']) !!}</td>
                    </tr>
                @endforeach
            </table>
        </div>
    </div>
</div>

<!-- this is the modal for the add/remove money routine: -->
<div class="modal fade" id="moneyManagementModal">



</div><!-- /.modal -->

@stop
@section('scripts')
<script type="text/javascript" src="js/piggy-banks.js"></script>
@stop
