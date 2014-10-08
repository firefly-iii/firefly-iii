@extends('layouts.default')
@section('content')
<div class="row">
    <div class="col-lg-12 col-md-12 col-sm-12">
        @if($view == 'session')
        <!-- warning for session date -->
        <p class="bg-primary" style="padding:15px;">
            This view is filtered to only show transactions between {{Session::get('start')->format('d M Y')}}
            and {{Session::get('end')->format('d M Y')}}.
        </p>
        @endif
    </div>
</div>
@if($transactions->count() > 0)
<div class="row">
    <div class="col-lg-12">
            @include('lists.transactions',['journals' => $transactions,'sum' => true])
    </div>
</div>
@else
<div class="row">
    <div class="col-lg-12">
        <h4>{{$repetition['date']}}
        </h4>
        <p><em>No transactions</em></p>
    </div>
</div>
@endif

@stop