@extends('layout.v3.session')
@section('content')
    <form method="POST" action="{{ route('currencies.store') }}" accept-charset="UTF-8" class="form-horizontal" id="store">
        <input name="_token" type="hidden" value="{{ csrf_token() }}">
        <div class="row">
            <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
                <div class="card mb-2">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('firefly.mandatoryFields') }}</h3>
                    </div>
                    <div class="card-body">
                        {!! ExpandedForm::text('name',null,['maxlength' =>  48]) !!}
                        {!! ExpandedForm::text('symbol',null,['maxlength' => 51]) !!}
                        {!! ExpandedForm::text('code',null,['maxlength' =>  51]) !!}
                        {!! ExpandedForm::integer('decimal_places',2,['maxlength' =>  2,'min' => 0,'max' =>  12]) !!}
                    </div>
                </div>

            </div>
        </div>
        <div class="row">
            <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">

                {{-- panel for options --}}
                <div class="card mb-2">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('firefly.options') }}</h3>
                    </div>
                    <div class="card-body">
                        {!! ExpandedForm::optionsList('create','currency') !!}
                    </div>
                    <div class="card-footer text-end">
                        <button type="submit" class="btn btn-success">
                            {{ __('firefly.store_currency') }}
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </form>
@endsection
@section('scripts')
    @vite(['js/pages/generic.js'])
@endsection
