@extends('layout.v3.session')
@section('content')

    <form method="POST" action="{{ route('rules.store') }}" accept-charset="UTF-8" class="form-horizontal" id="store">
        <input name="_token" type="hidden" value="{{ csrf_token() }}">
        <input type="hidden" name="rule_group_id" value="{{ $ruleGroup->id }}"/>
        <input type="hidden" name="return_to_bill" value="@if($returnToBill??false)true @else false @endif"/>
        <input type="hidden" name="bill_id" value="@if(null !== ($bill??null)){{ $bill->id }} @else 0 @endif"/>

        <input type="hidden" name="active" value="1"/>
        @if(null !== ($bill??null))
            <div class="row">
                <div class="col-lg-12 col-md-12 col-sm-12">
                    <div class="box box-success" id="mandatory">
                        <div class="card-header">
                            <h3 class="card-title">{{ trans('firefly.create_rule_for_bill', ['name' => $bill->name]) }}</h3>
                        </div>
                        <div class="card-body">
                            <p class="text-info">
                                {{ trans('firefly.create_rule_for_bill_txt', ['name' => $bill->name]) }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        @endif
        <div class="row">
            <div class="col-lg-6 col-md-6 col-sm-12">
                <div class="box box-primary" id="mandatory">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('firefly.mandatoryFields') }}</h3>
                    </div>
                    <div class="card-body">
                        {!! ExpandedForm::text('title') !!}
                        {!! ExpandedForm::select('trigger', all_journal_triggers()) !!}
                        {!! RuleForm::ruleGroupList('rule_group_id', $ruleGroup->id) !!}
                        {!! ExpandedForm::checkbox('stop_processing',1, null, ['helpText' => trans('firefly.rule_help_stop_processing')]) !!}
                        {!! ExpandedForm::checkbox('strict',1, null,['helpText' => trans('firefly.rule_help_strict')]) !!}
                    </div>
                </div>
            </div>
            <div class="col-lg-6 col-md-6 col-sm-12">

                {{-- optional fields --}}
                <div class="card mb-2">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('firefly.optionalFields') }}</h3>
                    </div>
                    <div class="card-body">
                        {!! ExpandedForm::textarea('description', null, ['helpText' => trans('firefly.field_supports_markdown')]) !!}
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-12 col-md-12 col-sm-12">
                <div class="card mb-2">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('firefly.rule_triggers') }}</h3>
                    </div>
                    <div class="box-body rule-trigger-box">
                        <table class="table table-sm table-bordered table-striped">
                            <thead>
                            <tr>
                                <th colspan="2">{{ __('firefly.trigger') }}</th>
                                <th>{{ __('firefly.is_not_rule_trigger') }}</th>
                                <th>{{ __('firefly.trigger_value') }}</th>
                                <th>{{ __('firefly.stop_processing_other_triggers') }}</th>
                            </tr>
                            </thead>
                            <tbody class="rule-trigger-tbody">
                            @foreach($oldTriggers as $trigger)
                                {!! $trigger !!}
                            @endforeach
                            </tbody>

                        </table>
                        <p>
                            <br/>
                            <button type="button" class="btn btn-outline-secondary add_rule_trigger">{{ __('firefly.add_rule_trigger') }}</button>
                            <a href="#" class="btn btn-outline-secondary test_rule_triggers"><span class="fa fa-flask"></span> {{ __('firefly.test_rule_triggers') }}</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        @include('rules.partials.test-trigger-modal')

        <div class="row">
            <div class="col-lg-12 col-md-12 col-sm-12">
                <div class="card mb-2">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('firefly.rule_actions') }}</h3>
                    </div>
                    <div class="box-body rule-action-box">
                        <table class="table table-sm table-bordered table-striped">
                            <thead>
                            <tr>
                                <th colspan="2">{{ __('firefly.action') }}</th>
                                <th>{{ __('firefly.action_value') }}</th>
                                <th>{{ __('firefly.stop_executing_other_actions') }}</th>
                            </tr>
                            </thead>
                            <tbody class="rule-action-tbody">
                            @foreach($oldActions as $action)
                                {!! $action !!}
                            @endforeach
                            </tbody>

                        </table>
                        <p>
                            <br/>
                            <a href="#" class="btn btn-outline-secondary add_rule_action">{{ __('firefly.add_rule_action') }}</a>
                        </p>
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
                        {!! ExpandedForm::checkbox('run_after_form',1,null, ['helpText' => trans('firefly.rule_run_after_creation')]) !!}
                        {!! ExpandedForm::optionsList('create','rule') !!}
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn text-end btn-success">{{ __('firefly.store_new_rule') }}</button>
                    </div>
                </div>

            </div>

        </div>
    </form>

@endsection
@section('scripts')
    @vite(['js/pages/generic.js'])
    <script type="text/javascript" src="v1/js/lib/typeahead/typeahead.bundle.min.js?v={{ $FF_BUILD_TIME }}" nonce="{{ $JS_NONCE }}"></script>
    <script type="text/javascript" nonce="{{ $JS_NONCE }}">
        var triggerCount = {{ $triggerCount }};
        var actionCount = {{ $actionCount }};
        var testRuleTriggersText = '{{ __('firefly.test_rule_triggers') }}';
    </script>
    <script type="text/javascript" src="v1/js/ff/rules/create-edit.js?v={{ $FF_BUILD_TIME }}" nonce="{{ $JS_NONCE }}"></script>

@endsection
@section('styles')
@endsection
