@extends('layout.v3.session')
@section('content')
    <div class="row">
        <div class="col-lg-12 col-md-12 col-sm-12">
            <div class="btn-group">
                <a href="{{ route('rule-groups.create') }}" id="new_rule_group" class="btn btn-success">{{ __('firefly.new_rule_group') }}</a>
                <a href="{{ route('rules.create') }}" class="btn btn-success new_rule">{{ __('firefly.new_rule') }}</a>
            </div>
            <p></p>
        </div>
    </div>
@if(1 === count($ruleGroups) && 0 === $ruleGroups[0]->count())
@php
$shownDemo = true
@endphp
<x-empty-page :route="route('rules.create', [$objectType])" type="rules" object-type="default" />
@endif
@foreach($ruleGroups as $ruleGroup)
    <div class="row mb-2">
        <div class="col-lg-12 col-md-12 col-sm-12">
            <div class="card rules-box" data-group="{{ $ruleGroup->id }}">
                    <div class="card-header">
                        <div class="row">
                            <div class="col">
                                <h3 class="card-title">
                                    @if($ruleGroup->active)
                                        {{ $ruleGroup->title }}
                                    @else
                                        <s>{{ $ruleGroup->title }}</s> ({{ strtolower(__('firefly.inactive')) }})
                                    @endif
                                </h3>
                            </div>
                            <div class="col text-end">
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-secondary-outline dropdown-toggle" type="button" id="card_header_{{ $ruleGroup->id }}" data-bs-toggle="dropdown" aria-expanded="false">
                                        <span class="bi bi-list"></span>
                                    </button>
                                    <ul class="dropdown-menu" aria-labelledby="card_header_{{ $ruleGroup->id }}">
                                        <li><a class="dropdown-item" href="{{ route('rule-groups.edit',$ruleGroup->id) }}"><span class="fa fa-fw fa-pencil"></span> {{ __('firefly.edit') }}</a></li>
                                        <li><a class="dropdown-item" href="{{ route('rule-groups.delete',$ruleGroup->id) }}"><span class="fa fa-fw fa-trash"></span> {{ __('firefly.delete') }}</a></li>
                                        <li><a class="dropdown-item" href="{{ route('rule-groups.select-transactions',$ruleGroup->id) }}"><span
                                                    class="fa fa-fw fa-power-off"></span> {{ trans('firefly.apply_rule_group_selection', ['title' => $ruleGroup->title]) }}
                                            </a></li>
                                        @if($ruleGroup->order > 1)
                                            <li><a href="#" class="dropdown-item move-group" data-direction="up" data-id="{{ $ruleGroup->id }}"><span
                                                        class="fa fa-fw fa-arrow-up"></span> {{ __('firefly.move_rule_group_up') }}</a></li>
                                        @endif
                                        @if($ruleGroup->order < count($ruleGroups))
                                            <li><a href="#" class="dropdown-item move-group" data-direction="down" data-id="{{ $ruleGroup->id }}"><span
                                                        class="fa fa-fw fa-arrow-down"></span> {{ __('firefly.move_rule_group_down') }}
                                                </a></li>
                                        @endif
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <p>
                            <em>{{ $ruleGroup->description }}</em>
                        </p>
                        <p>
                            <a href="{{ route('rules.create', $ruleGroup->id) }}"
                               class="btn btn-success new_rule">{{ __('firefly.new_rule') }}</a>
                        </p>

                        @if($ruleGroup->rules->count() > 0)
                            <table class="table table-sm table-hover table-striped group-rules">
                                <thead>
                                <tr>
                                    <th class="five">&nbsp;</th>
                                    <th class="ten">&nbsp;</th>
                                    <th class="ten">&nbsp;</th>
                                    <th class="quarter">{{ __('firefly.rule_name') }}</th>
                                    <th class="quarter hidden-xs">{{ __('firefly.rule_triggers') }}</th>
                                    <th class="quarter hidden-xs">{{ __('firefly.rule_actions') }}</th>
                                </tr>
                                </thead>
                                <tbody class="rule-connected-list">
                                @foreach($ruleGroup->rules as $rule)
                                    <tr class="single-rule" data-order="{{ $rule->order }}" data-id="{{ $rule->id }}" data-group-id="{{ $ruleGroup->id }}" data-position="{{ $loop->index }}">
                                        <td>
                                            <div class="btn-group btn-group-xs prio_buttons">
                                                <span class="fa fa-fw fa-bars rule-handle"></span>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-xs edit_buttons">
                                                <a title="{{ __('firefly.edit') }}" href="{{ route('rules.edit', $rule->id) }}"
                                                   class="btn btn-default"><span
                                                        class="fa fa-fw fa-pencil"></span></a>
                                                <a title="{{ __('firefly.delete') }}"
                                                   href="{{ route('rules.delete', $rule->id) }}"
                                                   class="btn btn-danger"><span
                                                        class="fa fa-fw fa-trash"></span></a>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-xs test_buttons">
                                                {{-- show which transactions would match --}}
                                                <a href="{{ route('rules.search',$rule->id) }}" class="btn btn-default {% if false == rule.strict %}test_rule_triggers{% endif %}" data-id="{{ $rule->id }}" title="{{ __('firefly.test_rule_triggers') }}"><span data-id="{{ $rule->id }}" class="fa fa-fw fa-flask"></span></a>
                                                @if($rule->active)
                                                    {{-- actually execute rule --}}
                                                    <a href="{{ route('rules.select-transactions',$rule->id) }}" class="btn btn-default" title=" {{ trans('firefly.apply_rule_selection', ['title' => $rule->title]) }}"><span class="fa fa-fw fa-power-off "></span></a>
                                                @endif

                                                {{--  duplicate rule --}}
                                                <a href="#" class="btn btn-default duplicate-rule" data-id="{{ $rule->id }}" title=" {{ trans('firefly.duplicate_rule', ['title' => $rule->title]) }}"><span class="fa fa-fw fa-copy"></span></a>
                                            </div>
                                        </td>
                                        <td class="markdown">
                                            @if($rule->active)
                                                {{ $rule->title }}
                                            @else
                                                <s>{{ $rule->title }}</s> ({{ strtolower(__('firefly.inactive')) }})
                                            @endif
                                                @if($rule->stop_processing)
                                                <span class="fa fa-stop-circle-o"></span>
                                            @endif
                                                @if('' !== $rule->description)
                                                <small class="hidden-xs
                                                   @if(!$rule->active)
                                                        text-muted
                                                   @endif
                                                   "
                                                ><br/>{{ parse_markdown($rule->description) }}</small>
                                               @endif
                                            <small>(@if($rule->strict)<span class="text-danger">{{ __('firefly.rule_is_strict') }}</span>@else<span class="text-success">{{ __('firefly.rule_is_not_strict') }}</span>@endif&ZeroWidthSpace;)</small>
                                        </td>
                                        <td class="hidden-xs">
                                            @if($rule->ruleTriggers->count() > 0)
                                                <ul class="small rule-trigger-list" data-count="{{ $rule->ruleTriggers->count() }}" data-id="{{ $rule->id }}">
                                                    @foreach($rule->ruleTriggers as $trigger)
                                                        @if('user_action' === $trigger->trigger_type)
                                                            <li
                                                                @if(!$rule->active)
                                                                    class="text-muted"
                                                                @endif
                                                                data-id="{{ $trigger->id }}">
                                                                {{ trans(('firefly.rule_trigger_' . get_root_search_operator($trigger->trigger_type)), ['trigger_value' => $trigger->trigger_value]) }}
                                                                @if($trigger->stop_processing)
                                                                    <span class="fa fa-stop-circle-o"></span>
                                                                @endif
                                                            </li>
                                                        @endif
                                                    @endforeach
                                                </ul>
                                                <ul class="small rule-triggers-show hidden pointer" data-id="{{ $rule->id }}">
                                                    <li data-id="{{ $rule->id }}">{{ __('firefly.show_triggers') }}</li>
                                                </ul>
                                            @endif
                                        </td>
                                        <td class="hidden-xs">
                                            @if($rule->ruleActions->count() > 0)
                                                <ul class="small rule-action-list" data-count="{{ $rule->ruleActions->count() }}" data-id="{{ $rule->id }}">
                                                    @foreach($rule->ruleActions as $action)
                                                        <li
                                                            @if(!$rule->active)
                                                                class="text-muted"
                                                            @endif
                                                            data-id="{{ $action->id }}">{{ trans(('firefly.rule_action_' . $action->action_type), ['action_value' => $action->action_value]) }}
                                                            @if($action->stop_processing)
                                                                <span class="fa fa-stop-circle-o"></span>
                                                            @endif
                                                        </li>
                                                    @endforeach
                                                </ul>
                                                <ul class="small rule-actions-show hidden pointer" data-id="{{ $rule->id }}">
                                                    <li>{{ __('firefly.show_actions') }}</li>
                                                </ul>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        @else
                            <p>
                                <em>{{ __('firefly.no_rules_in_group') }}</em>
                            </p>
                        @endif
                        <p>
                            <br/>
                            <a href="{{ route('rules.create', $ruleGroup->id) }}"
                               class="btn btn-success new_rule">{{ __('firefly.new_rule') }}</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
@endforeach

    {% include 'rules.partials.test-trigger-modal' %}

    <div class="row">
        <div class="col-lg-12 col-md-12 col-sm-12">
            <a href="{{ route('rule-groups.create') }}" class="btn btn-success">{{ __('firefly.new_rule_group') }}</a>
        </div>
    </div>

@endsection
@section('scripts')
    @vite(['js/pages/rules/index.js'])
    <script type="text/javascript" nonce="{{ $JS_NONCE }}">
        var moveRuleGroupUrl = '{{ route('rule-groups.move') }}';
        var duplicateRuleUrl = '{{ route('rules.duplicate') }}';
    </script>
    <script type="text/javascript" src="v1/js/lib/jquery-ui.min.js?v={{ $FF_BUILD_TIME }}" nonce="{{ $JS_NONCE }}"></script>
    <script type="text/javascript" src="v1/js/ff/rules/index.js?v={{ $FF_BUILD_TIME }}" nonce="{{ $JS_NONCE }}"></script>
@endsection
