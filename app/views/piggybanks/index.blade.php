@extends('layouts.default')
@section('content')
{{ Breadcrumbs::renderIfExists(Route::getCurrentRoute()->getName()) }}
<div class="row">
    @foreach($piggyBanks as $piggyBank)
        <div class="col-lg-6 col-md-6 col-sm-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <i class="fa fa-fw fa-rocket"></i> <a href="{{route('piggybanks.show',$piggyBank->id)}}" title="{{{$piggyBank->name}}}">{{{$piggyBank->name}}}</a>
                </div>
                <div class="panel-body">
                    <div class="row">
                        <div class="col-lg-2 col-md-3 col-sm-4">
                            {{mf($piggyBank->savedSoFar,true)}}
                        </div>
                        <div class="col-lg-8 col-md-6 col-sm-4">
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
                        <div class="col-lg-2 col-md-3 col-sm-4">
                            {{mf($piggyBank->targetamount,true)}}
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-2 col-md-3 col-sm-4">
                            <div class="btn-group btn-group-xs">
                                @if($piggyBank->leftToSave > 0)
                                    <a href="#" class="btn btn-default addMoney" data-id="{{{$piggyBank->id}}}"><span data-id="{{{$piggyBank->id}}}" class="glyphicon glyphicon-plus"></span></a>
                                @endif
                                <a href="#" class="btn btn-default removeMoney" data-id="{{{$piggyBank->id}}}"><span data-id="{{{$piggyBank->id}}}" class="glyphicon glyphicon-minus"></span></a>
                            </div>
                        </div>
                        <div class="col-lg-8 col-md-6 col-sm-4">
                            <div class="btn-group btn-group-xs">
                                <a href="{{route('piggybanks.edit',$piggyBank->id)}}" class="btn btn-default"><span class="glyphicon glyphicon-pencil"></span></a>
                                <a href="{{route('piggybanks.delete',$piggyBank->id)}}" class="btn btn-danger"><span class="glyphicon glyphicon-trash"></span></a>
                            </div>
                        </div>
                        <div class="col-lg-2 col-md-3 col-sm-4">
                            @if($piggyBank->leftToSave > 0)
                                {{mf($piggyBank->leftToSave)}}
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
    <div class="col-lg-6 col-md-6 col-sm-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <i class="fa fa-fw fa-plus"></i> Create piggy bank
            </div>
            <div class="panel-body">
                <div class="row">
                    <div class="col-lg-8 col-md-6 col-sm-4 col-lg-offset-2 col-md-offset-3 col-sm-offset-4">
                        <a href="{{route('piggybanks.create')}}" class="btn btn-success">Create new piggy bank</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-12 col-md-12 col-sm-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <i class="fa fa-fw fa-money"></i> Account status
            </div>
            <div class="panel-body">
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
                        <td>{{mf($info['balance'])}}</td>
                        <td>{{mf($info['leftForPiggybanks'])}}</td>
                        <td>{{mf($info['sumOfTargets'])}}</td>
                        <td>{{mf($info['sumOfSaved'])}}</td>
                        <td>{{mf($info['leftToSave'])}}</td>
                    </tr>
                @endforeach
            </table>
            </div>
        </div>
    </div>
</div>

<!-- this is the modal for the add/remove money routine: -->
<div class="modal fade" id="moneyManagementModal">
</div><!-- /.modal -->

@stop
@section('scripts')
{{HTML::script('assets/javascript/firefly/piggybanks.js')}}
@stop