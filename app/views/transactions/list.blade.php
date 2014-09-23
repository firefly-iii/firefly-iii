@extends('layouts.default')
@section('content')
<div class="row">
    <div class="col-lg-12 col-sm-12 col-md-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <i class="fa {{$subTitleIcon}}"></i> {{{$subTitle}}}
            </div>
            <div class="panel-body">
                <table id="transactionTable" class="table table-striped table-bordered" >
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Description</th>
                        <th data-dynatable-column="amount">Amount (&euro;)</th>
                        <th>From</th>
                        <th>To</th>
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
var URL = '{{route('json.'.$what)}}';
var display = '{{{$what}}}';
</script>
{{HTML::script('assets/javascript/typeahead/bootstrap3-typeahead.min.js')}}
{{HTML::script('assets/javascript/datatables/jquery.dataTables.min.js')}}
{{HTML::script('assets/javascript/datatables/dataTables.bootstrap.js')}}
{{HTML::script('assets/javascript/firefly/transactions.js')}}
@stop
@section('styles')
{{HTML::style('assets/stylesheets/datatables/dataTables.bootstrap.css')}}
@stop