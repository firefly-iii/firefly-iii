@extends('layouts.default')
@section('content')
{!! Breadcrumbs::renderIfExists(Route::getCurrentRoute()->getName(), $bill) !!}
<div class="row">
    <div class="col-lg-6 col-sm-12 col-md-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <i class="fa fa-rotate-right"></i> {{{$bill->name}}}

                @if($bill->active)
                    <i class="fa fa-check fa-fw" title="Active"></i>
                @else
                    <i class="fa fa-times fa-fw" title="Inactive"></i>
                @endif

                @if($bill->automatch)
                    <i class="fa fa-check fa-fw" title="Automatically matched by Firefly"></i>
                @else
                    <i class="fa fa-times fa-fw" title="Not automatically matched by Firefly"></i>
                @endif

                <!-- ACTIONS MENU -->
                <div class="pull-right">
                    <div class="btn-group">
                        <button type="button" class="btn btn-default btn-xs dropdown-toggle" data-toggle="dropdown">
                            Actions
                            <span class="caret"></span>
                        </button>
                        <ul class="dropdown-menu pull-right" role="menu">
                            <li><a href="{{route('bills.edit',$bill->id)}}"><i class="fa fa-fw fa-pencil"></i> edit</a></li>
                            <li><a href="{{route('bills.delete',$bill->id)}}"><i class="fa fa-fw fa-trash-o"></i> delete</a></li>
                        </ul>
                    </div>
                </div>

            </div>
            <div class="panel-body">
                <table class="table">
                    <tr>
                        <td colspan="2">
                        Matching on
                            @foreach(explode(',',$bill->match) as $word)
                                <span class="label label-info">{{{$word}}}</span>
                            @endforeach
                            between {!! Amount::format($bill->amount_min) !!} and {!! Amount::format($bill->amount_max) !!}.
                            Repeats {!! $bill->repeat_freq !!}.</td>

                    </tr>
                    <tr>
                        <td>Next expected match</td>
                        <td>
                            @if($bill->nextExpectedMatch)
                                {{$bill->nextExpectedMatch->format('j F Y')}}
                            @else
                                <em>Unknown</em>
                            @endif
                            </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-6 col-sm-12 col-md-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                More
            </div>
            <div class="panel-body">
                <p>
                    <a href="{{route('bills.rescan',$bill->id)}}" class="btn btn-default">Rescan old transactions</a>
                </p>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-12 col-sm-12 col-md-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                Chart
            </div>
            <div class="panel-body">
                <div id="bill-overview"></div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-12 col-sm-12 col-md-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                Connected transaction journals
            </div>
            <div class="panel-body">
                @include('list.journals-full',['sorting' => false])
            </div>
        </div>
    </div>
</div>

@stop

@section('scripts')
<script type="text/javascript">
    var billID = {{{$bill->id}}};
    var currencyCode = '{{Amount::getCurrencyCode()}}';
</script>
<!-- load the libraries and scripts necessary for Google Charts: -->
<script type="text/javascript" src="https://www.google.com/jsapi"></script>
<script type="text/javascript" src="js/gcharts.options.js"></script>
<script type="text/javascript" src="js/gcharts.js"></script>

<script type="text/javascript" src="js/bills.js"></script>
@stop
