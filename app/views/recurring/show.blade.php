@extends('layouts.default')
@section('content')
<div class="row">
    <div class="col-lg-6 col-sm-6 col-md-12">
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
                            @foreach(explode(' ',$recurring->match) as $word)
                                <span class="label label-info">{{{$word}}}</span>
                            @endforeach
                            between {{mf($recurring->amount_min)}} and {{mf($recurring->amount_max)}}.
                            Repeats {{$recurring->repeat_freq}}.</td>

                    </tr>
                    <tr>
                        <td>Next reminder</td>
                        <td>TODO TODO</td>
                    </tr>
                </table>
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
                <table id="transactionTable" class="table table-striped table-bordered" >
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Description</th>
                        <th>Amount (&euro;)</th>
                        <th>From</th>
                        <th>To</th>
                        <th>Budget / category</th>
                        <th>ID</th>
                    </tr>
                </thead>
                </table>
            </div>
        </div>
    </div>
</div>

@stop

@section('scripts')
    <script type="text/javascript">
        var URL = '{{route('json.recurringjournals',$recurring->id)}}';
</script>
{{HTML::script('assets/javascript/typeahead/bootstrap3-typeahead.min.js')}}
{{HTML::script('assets/javascript/datatables/jquery.dataTables.min.js')}}
{{HTML::script('assets/javascript/datatables/dataTables.bootstrap.js')}}
{{HTML::script('assets/javascript/firefly/recurring.js')}}
@stop
@section('styles')
{{HTML::style('assets/stylesheets/datatables/dataTables.bootstrap.css')}}
@stop