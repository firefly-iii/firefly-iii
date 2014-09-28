@extends('layouts.default')
@section('content')
<div class="row">
    <div class="col-lg-12 col-sm-12 col-md-12">
    <div class="panel panel-default">
        <div class="panel-heading">
            <i class="fa {{$mainTitleIcon}}"></i> {{{$title}}}
        </div>
        <div class="panel-body">
            <table class="table table-striped" id="recurringTable">
            <thead>
                <tr>
                    <th>name</th>
                    <th>match</th>
                    <th>amount_min</th>
                    <th>amount_max</th>
                    <th>date</th>
                    <th>active</th>
                    <th>automatch</th>
                    <th>repeat_freq</th>
                    <th>id</th>
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
var URL = '{{route('json.recurring')}}';
</script>
{{HTML::script('assets/javascript/typeahead/bootstrap3-typeahead.min.js')}}
{{HTML::script('assets/javascript/datatables/jquery.dataTables.min.js')}}
{{HTML::script('assets/javascript/datatables/dataTables.bootstrap.js')}}
{{HTML::script('assets/javascript/firefly/recurring.js')}}
@stop
@section('styles')
{{HTML::style('assets/stylesheets/datatables/dataTables.bootstrap.css')}}
@stop