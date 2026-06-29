@extends('layout.v3.session')
@section('content')

    <form method="post" action="{{ route('rules.update', $rule->id) }}" class="form-horizontal" accept-charset="UTF-8"
          enctype="multipart/form-data">
        <input type="hidden" name="_token" value="{{ csrf_token() }}"/>
        <input type="hidden" name="id" value="{{ $rule->id }}"/>
        <div class="row">
            <div class="col-lg-6 col-md-6 col-sm-12">
                <div class="card mb-2">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('firefly.mandatoryFields') }}</h3>
                    </div>
                    <div class="card-body">
                        {!! ExpandedForm::text('title', $rule->title) !!}
                        {!! RuleForm::ruleGroupList('rule_group_id', $rule->rule_group_id) !!}
                        {!! ExpandedForm::select('trigger',all_journal_triggers(), $primaryTrigger) !!}
                        {!! ExpandedForm::checkbox('active', 1, null, ['helpText' => trans('firefly.rule_help_active')]) !!}

                        {!! ExpandedForm::checkbox('stop_processing',1,$rule->stop_processing, ['helpText' => trans('firefly.rule_help_stop_processing')]) !!}
                        {!! ExpandedForm::checkbox('strict',1,$rule->strict, ['helpText' => trans('firefly.rule_help_strict')]) !!}
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
                        {!! ExpandedForm::textarea('description', $rule->description, ['helpText' => trans('firefly.field_supports_markdown')]) !!}
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
                    <div class="card    -body rule-trigger-box">
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
                            <a href="#" class="btn btn-outline-secondary add_rule_trigger">{{ __('firefly.add_rule_trigger') }}</a>
                            <a href="#" class="btn btn-outline-secondary test_rule_triggers"><span
                                    class="fa fa-flask"></span> {{ __('firefly.test_rule_triggers') }}</a>
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
                    <div class="card-body">
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
                        {!! ExpandedForm::checkbox('run_after_form',1,null, ['helpText' => trans('firefly.rule_run_after_edit')]) !!}
                        {!! ExpandedForm::optionsList('update','rule') !!}
                    </div>
                    <div class="card-footer text-end">
                        <button type="submit" class="btn btn-success">{{ __('firefly.update_rule') }}</button>
                    </div>
                </div>

            </div>

        </div>
    </form>


@endsection
@section('scripts')
    @vite(['js/pages/generic.js'])
    <script type="text/javascript" src="v1/js/lib/typeahead/typeahead.bundle.min.js?v={{ $FF_BUILD_TIME }}"
            nonce="{{ $JS_NONCE }}"></script>
    <script type="text/javascript" nonce="{{ $JS_NONCE }}">
        var triggerCount = {{ $triggerCount }};
        var actionCount = {{ $actionCount }};
        var testRuleTriggersText = '{{ __('firefly.test_rule_triggers') }}';
    </script>
    <script type="text/javascript" src="v1/js/ff/rules/create-edit.js?v={{ $FF_BUILD_TIME }}"
            nonce="{{ $JS_NONCE }}"></script>

@endsection
