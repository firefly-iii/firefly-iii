@extends('layouts.default')
@section('content')
    {{ Breadcrumbs::renderIfExists(Route::getCurrentRoute()->getName()) }}

    <div class="row">
        <div class="col-lg-6 col-sm-12 col-md-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    Currencies
                </div>
                <div class="panel-body">
                    <p class="text-info">
                        Firefly III supports various currencies which you can set and enable here.
                    </p>
                    <ul>
                    @if(count($currencies) > 0)
                        @foreach($currencies as $currency)
                            <li>
                                <a href="{{route('currency.edit',$currency->id)}}"><i class="fa fa-fw fa-pencil"></i></a>
                                <a href="{{route('currency.delete',$currency->id)}}"><i class="fa fa-fw fa-trash"></i></a>
                                {{{$currency->name}}} ({{{$currency->code}}}) ({{{$currency->symbol}}})
                                @if($currency->id == $defaultCurrency->id)
                                    <span class="label label-success">default</span>
                                @else
                                    <span class="label label-default"><a style="color:#fff" href="{{route('currency.default',$currency->id)}}">make default</a></span>

                                @endif
                            </li>
                        @endforeach
                    @endif
                        <li><a href="{{route('currency.create')}}"><i class="fa fa-fw fa-plus-circle"></i> Add another currency</a></li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="col-lg-6 col-sm-12 col-md-12">
            <div class="panel panel-green">
                <div class="panel-heading">
                    Supported
                </div>
                <div class="panel-body">
                    <ul>
                        <li>Set the default currency display;</li>
                        <li>Set the default currency for new transactions;</li>
                        <li>Add, modify and remove supported currencies.</li>
                    </ul>
                </div>
            </div>
            <div class="panel panel-red">
                <div class="panel-heading">
                    Not supported yet
                </div>
                <div class="panel-body">
                    <ul>
                        <li>Display the actual currency of a transaction<br />
                            <small>See the help-page.</small></li>
                        <li>
                            Update a transaction's currency.
                        </li>

                    </ul>

                </div>
            </div>
        </div>
    </div>
@stop
