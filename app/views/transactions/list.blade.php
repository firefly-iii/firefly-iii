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
<?php echo javascript_include_tag('transactions'); ?>
@stop
@section('styles')
<?php echo stylesheet_link_tag('transactions'); ?>
@stop