@extends('layout.v3.session')
@section('content')

    <form method="POST" action="{{ route('rules.execute', $rule->id) }}" accept-charset="UTF-8" class="form-horizontal" id="execute-rule">
        <input name="_token" type="hidden" value="{{ csrf_token() }}">

        <div class="row">
            <div class="col-lg-12 col-sm-12 col-xs-12">

                <div class="card mb-2">
                    <div class="card-header">
                        <h3 class="card-title">{{ $subTitle }}</h3>
                    </div>
                    <div class="card-body">
                        <div id="form-body">
                            <p>
                                {{ trans('firefly.apply_rule_selection_intro', ['title' => $rule->title]) }}
                            </p>
                            <div class="row">
                                <div class="col-lg-6 col-md-8 col-sm-12 col-xs-12">
                                    {!! ExpandedForm::date('start') !!}
                                    {!! ExpandedForm::date('end') !!}
                                    {!! AccountForm::assetAccountCheckList('accounts', ['select_all' => true,  'class' => 'account-checkbox', 'label' => trans('firefly.include_transactions_from_accounts') ]) !!}
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer text-end">
                        <input type="submit" name="submit" value="{{ __('firefly.execute') }}" id="do-execute-button" class="btn btn-success"/>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection
@section('scripts')
    @vite(['js/pages/generic.js'])
    <script type="text/javascript" src="v1/js/ff/rules/select-transactions.js?v={{ $FF_BUILD_TIME }}" nonce="{{ $JS_NONCE }}"></script>
@endsection

@section('styles')
@endsection
