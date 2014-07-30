@extends('layouts.default')
@section('content')



<div class="row">
    <div class="col-lg-12 col-md-12 col-sm-12">
        <h1>Firefly
            <small>Category "{{{$category->name}}}"</small>
        </h1>
        <p class="lead">Use categories to group your expenses</p>
        <p class="text-info">
            Use categories to group expenses by hobby, for certain types of groceries or what bills are for.
            Expenses grouped in categories do not have to reoccur every month or every week, like budgets.
        </p>
        <p class="text-info">
            This overview will show you the expenses you've made in each [period] and show you the actual
            transactions for the currently selected period.
        </p>
    </div>
</div>


@include('partials.date_nav')
<div class="row">
    <div class="col-lg-12 col-md-12 col-sm-12">
        <div id="chart"><p class="small text-center">(Some chart here)</p></div>
        </div>
    </div>


<div class="row">
    <div class="col-lg-12 col-md-12 col-sm-12">
        <h4>Transactions<small> in current range</small></h4>
            @include('lists.transactions',['journals' => $journals,'sum' => true])
        </div>
    </div>



@stop
@section('scripts')
<script type="text/javascript">
    var categoryID = {{$category->id}};
</script>
<?php echo javascript_include_tag('categories'); ?>
@stop