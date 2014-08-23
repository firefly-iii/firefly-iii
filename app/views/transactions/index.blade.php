@extends('layouts.default')
@section('content')


@if($filtered === true)
    <p class="bg-primary" style="padding:15px;">
        This view is filtered to show only the transactions between
        {{$filters['start']->format('M jS, Y')}} and {{$filters['end']->format('M jS, Y')}}.
    </p>
    <p class="bg-info" style="padding:15px;">
        <a href="{{route('transactions.index')}}" class="text-info">Reset the filter.</a>
    </p>
@endif


@include('paginated.transactions')


@stop

