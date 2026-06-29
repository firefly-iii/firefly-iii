@extends('layout.v3.session')
@section('content')
    <form method="post" action="{{ route('budgets.update',$budget->id) }}" class="form-horizontal" accept-charset="UTF-8"
          enctype="multipart/form-data">
        <input type="hidden" name="_token" value="{{ csrf_token() }}"/>
        <input type="hidden" name="id" value="{{ $budget->id }}"/>
        <div class="row">
            <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
                <div class="card mb-2">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('firefly.mandatoryFields') }}</h3>
                    </div>
                    <div class="card-body">
                        {!! ExpandedForm::checkbox('active', 1) !!}
                        {!! ExpandedForm::text('name', $budget->name) !!}
                    </div>
                </div>

            </div>
            <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
                {{-- panel for auto-budget --}}
                <div class="card mb-2">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('firefly.optionalFields') }}</h3>
                    </div>
                    <div class="card-body">
                        {!! ExpandedForm::select('auto_budget_type', $autoBudgetTypes, $autoBudget?->auto_budget_type) !!}
                        {!! CurrencyForm::currencyList('auto_budget_currency_id', $autoBudget?->transaction_currency_id) !!}
                        {!! ExpandedForm::amountNoCurrency('auto_budget_amount', $preFilled['auto_budget_amount'] ?? '') !!}
                        {!! ExpandedForm::select('auto_budget_period', $autoBudgetPeriods, $autoBudget?->period) !!}
                        {!! ExpandedForm::textarea('notes',$preFilled['notes'],['helpText' => trans('firefly.field_supports_markdown')]) !!}
                        {!! ExpandedForm::file('attachments[]', ['multiple' => 'multiple','helpText' => trans('firefly.upload_max_file_size', ['size' => print_nice_filesize($uploadSize)])]) !!}
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
                        {!! ExpandedForm::optionsList('update','budget') !!}
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn text-end btn-success">{{ __('firefly.update_budget') }}</button>
                    </div>
                </div>
            </div>
        </div>

    </form>

@endsection
@section('scripts')
    @vite(['js/pages/generic.js'])
    <script type="text/javascript" src="v1/js/ff/budgets/edit.js?v={{ $FF_BUILD_TIME }}" nonce="{{ $JS_NONCE }}"></script>
@endsection
