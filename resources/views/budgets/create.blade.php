@extends('layout.v3.session')
@section('content')
    <form method="POST" action="{{ route('budgets.store') }}" accept-charset="UTF-8" class="form-horizontal" id="store" enctype="multipart/form-data">
        <input name="_token" type="hidden" value="{{ csrf_token() }}">
        <input name="active" type="hidden" value="1">

        <div class="row">
            <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('firefly.mandatoryFields') }}</h3>
                    </div>
                    <div class="card-body">
                        {!! ExpandedForm::text('name') !!}
                    </div>
                </div>
            </div>
            <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
                {{-- panel for auto-budget --}}
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('firefly.optionalFields') }}</h3>
                    </div>
                    <div class="card-body">
                        {!! ExpandedForm::select('auto_budget_type', $autoBudgetTypes,null, ['helpText' => trans('firefly.auto_budget_help')]) !!}
                        {!! CurrencyForm::currencyList('auto_budget_currency_id') !!}
                        {!! ExpandedForm::amountNoCurrency('auto_budget_amount') !!}
                        {!! ExpandedForm::select('auto_budget_period', $autoBudgetPeriods, null) !!}
                        {!! ExpandedForm::file('attachments[]', ['multiple' => 'multiple','helpText' => trans('firefly.upload_max_file_size', ['size' => print_nice_filesize($uploadSize)])]) !!}
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
                {{-- panel for options --}}
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('firefly.options') }}</h3>
                    </div>
                    <div class="card-body">
                        {!! ExpandedForm::optionsList('create','budget') !!}
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn text-end btn-success">{{ __('firefly.store_new_budget') }}</button>
                    </div>
                </div>
            </div>

        </div>
    </form>


@endsection
@section('scripts')
    @vite(['js/pages/generic.js'])
    <script type="text/javascript" src="v1/js/ff/budgets/create.js?v={{ $FF_BUILD_TIME }}" nonce="{{ $JS_NONCE }}"></script>
@endsection
