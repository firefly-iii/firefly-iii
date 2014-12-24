@extends('layouts.default')
@section('content')
{{ Breadcrumbs::renderIfExists(Route::getCurrentRoute()->getName(), $recurring) }}
<div class="row">
    <div class="col-lg-6 col-sm-12 col-md-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <i class="fa fa-rotate-right"></i> {{{$recurring->name}}}

                @if($recurring->active)
                    <span class="glyphicon glyphicon-ok" title="Active"></span>
                @else
                    <span class="glyphicon glyphicon-remove" title="Inactive"></span>
                @endif

                @if($recurring->automatch)
                    <span class="glyphicon glyphicon-ok" title="Automatically matched by Firefly"></span>
                @else
                    <span class="glyphicon glyphicon-remove" title="Not automatically matched by Firefly"></span>
                @endif

                <!-- ACTIONS MENU -->
                <div class="pull-right">
                    <div class="btn-group">
                        <button type="button" class="btn btn-default btn-xs dropdown-toggle" data-toggle="dropdown">
                            Actions
                            <span class="caret"></span>
                        </button>
                        <ul class="dropdown-menu pull-right" role="menu">
                            <li><a href="{{route('recurring.edit',$recurring->id)}}"><span class="glyphicon glyphicon-pencil"></span> edit</a></li>
                            <li><a href="{{route('recurring.delete',$recurring->id)}}"><span class="glyphicon glyphicon-trash"></span> delete</a></li>
                        </ul>
                    </div>
                </div>

            </div>
            <div class="panel-body">
                <table class="table">
                    <tr>
                        <td colspan="2">
                        Matching on
                            @foreach(explode(',',$recurring->match) as $word)
                                <span class="label label-info">{{{$word}}}</span>
                            @endforeach
                            between {{mf($recurring->amount_min)}} and {{mf($recurring->amount_max)}}.
                            Repeats {{$recurring->repeat_freq}}.</td>

                    </tr>
                    <tr>
                        <td>Next expected match</td>
                        <td>
                        <?php $nextExpectedMatch = $recurring->nextExpectedMatch();?>
                            @if($nextExpectedMatch)
                                {{$nextExpectedMatch->format('j F Y')}}
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
                    <a href="{{route('recurring.rescan',$recurring->id)}}" class="btn btn-default">Rescan old transactions</a>
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
                <div id="recurring-overview"></div>
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
                @include('list.journals-full')
            </div>
        </div>
    </div>
</div>

@stop

@section('scripts')
<script type="text/javascript">
    var recurringID = {{{$recurring->id}}};
    var currencyCode = '{{getCurrencyCode()}}';
</script>
<!-- load the libraries and scripts necessary for Google Charts: -->
<script type="text/javascript" src="https://www.google.com/jsapi"></script>
{{HTML::script('assets/javascript/firefly/gcharts.options.js')}}
{{HTML::script('assets/javascript/firefly/gcharts.js')}}

{{HTML::script('assets/javascript/firefly/recurring.js')}}
@stop