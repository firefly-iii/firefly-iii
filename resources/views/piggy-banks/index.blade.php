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
    <div class="col-lg-12 col-md-12 col-sm-12">
        <div class="panel panel-default">
            <div class="panel-heading">Piggy banks</div>
            @include('list.piggy-banks')
        </div>
    </div>
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
    <script src="js/jquery-ui.min.js" type="text/javascript"></script>
    <script type="text/javascript" src="js/piggy-banks.js"></script>
@stop
