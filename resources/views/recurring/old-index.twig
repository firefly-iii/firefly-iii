@extends('layout.v3.session')


    {{ Breadcrumbs.render(Route.getCurrentRoute.getName) }}
@endsection

@section('content')
    <!-- block with list of recurring transaction -->
    {% if total > 0 %}
        <div class="row">
            <div class="col-lg-12 col-md-12 col-sm-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            {{ 'recurrences'|_ }}
                        </h3>
                        <div class="box-tools text-end">
                            <div class="btn-group">
                                <button class="btn btn-box-tool dropdown-toggle" data-toggle="dropdown"><span class="bi bi-list"></span></button>
                                <ul class="dropdown-menu" role="menu">
                                    <li><a href="{{ route('recurring.create') }}"><span class="bi bi-plus-circle"></span> {{ ('make_new_recurring')|_ }}</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="card-body p-0">
                        <div class="p-2">
                            <a href="{{ route('recurring.create') }}" class="btn btn-success"><span class="bi bi-plus-circle"></span> {{ ('make_new_recurring')|_ }}
                            </a>
                        </div>

                        <!-- list of recurring here -->
                        <div class="pl-2">
                            {{ paginator.links('pagination.bootstrap-4')|raw }}
                        </div>
                        <table class="table table-responsive  table-hover sortable">
                            <thead>
                            <tr>
                                <th class="hidden-sm hidden-xs" data-defaultsort="disabled">&nbsp;</th>
                                <th data-defaultsign="az">{{ trans('list.title') }}</th>
                                <th data-defaultsort="disabled">{{ trans('list.transaction_s') }}</th>
                                <th data-defaultsort="disabled">{{ trans('list.repetitions') }}</th>
                                <th data-defaultsign="month" data-dateformat="{{ madMomentJS }}">{{ trans('list.next_due') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            {% for rt in paginator %}
                                <tr>
                                    <td class="hidden-sm hidden-xs">
                                        <div class="btn-group btn-group-sm edit_tr_buttons">
                                            <a class="btn btn-outline-secondary btn-xs" title="{{ __('firefly.edit') }}" href="{{ route('recurring.edit',rt.id) }}"><span
                                                    class="bi bi-pencil"></span></a><a class="btn btn-danger btn-xs" title="{{ __('firefly.delete') }}"
                                                                                             href="{{ route('recurring.delete',rt.id) }}"><span
                                                    class="bi bi-trash"></span></a>
                                        </div>
                                    </td>
                                    <td data-value="{{ rt.title }}">
                                        {% if rt.attachments > 0 %}
                                            <span class="bi bi-paperclip"></span>
                                        @endif
                                        {% if rt.active == false %}<s>@endif
                                            {{ rt.type|_ }}:
                                            <a href="{{ route('recurring.show',rt.id) }}">{{ rt.title }}</a>
                                            {% if rt.active == false %}</s> ({{ 'inactive'|_|lower }})@endif
                                        {% if rt.description|length > 0 %}
                                            <small><br>{{ rt.description }}</small>
                                        @endif


                                    </td>
                                    <td data-value="0">
                                        <ol>
                                            {% for rtt in rt.transactions %}
                                                <li>
                                                    {{-- normal amount + comma --}}
                                                    {{ format_amount_by_symbol(rtt['amount'],rtt['currency_symbol'],rtt['currency_decimal_places']) }}{% if rtt['foreign_amount'] == null %},@endif

                                                    {{-- foreign amount + comma --}}
                                                    {% if null != rtt['foreign_amount'] %}
                                                        ({{ format_amount_by_symbol(rtt['foreign_amount'],rtt['foreign_currency_symbol'],rtt['foreign_currency_decimal_places']) }}),
                                                    @endif
                                                    <a href="{{ route('accounts.show', rtt['source_id']) }}">{{ rtt['source_name'] }}</a>
                                                    &rarr;
                                                    <a href="{{ route('accounts.show', rtt['destination_id']) }}">{{ rtt['destination_name'] }}</a>
                                                </li>
                                            @endforeach
                                        </ol>
                                    </td>
                                    <td>
                                        {% if null != rt.repeat_until and today > rt.repeat_until %}
                                            <span class="text-danger">
                                            {{ trans('firefly.repeat_until_in_past', {date: rt.repeat_until.isoFormat($monthAndDayFormat) }) }}
                                        </span>
                                        @endif
                                        <ul>
                                            {% for rep in rt.repetitions %}
                                                <li>{{ rep.description }}
                                                    {% if rep.repetition_skip == 1 %}
                                                        ({{ trans('firefly.recurring_skips_one')|lower }}).
                                                    @endif
                                                    {% if rep.repetition_skip > 1 %}
                                                        ({{ trans('firefly.recurring_skips_more', {count: rep.repetition_skip})|lower }}).
                                                    @endif
                                                    {% if rep.weekend == 3 %}
                                                        <br>{{ 'will_jump_friday'|_ }}
                                                    @endif
                                                    {% if rep.weekend == 4 %}
                                                        <br>{{ 'will_jump_monday'|_ }}
                                                    @endif
                                                    {% if rep.weekend == 2 %}
                                                        <br>{{ 'except_weekends'|_ }}
                                                    @endif
                                                </li>
                                            @endforeach
                                        </ul>
                                        <p>
                                            {% if null == rt.repeat_until and rt.repetitions == 0 %}
                                                {{ 'recurring_repeats_forever'|_ }}.
                                            @endif
                                            {% if null != rt.repeat_until and rt.repetitions == 0 %}
                                                {{ trans('firefly.recurring_repeats_until', {date: rt.repeat_until.isoFormat($monthAndDayFormat)}) }}.
                                            @endif
                                            {% if null == rt.repeat_until and rt.nr_of_repetitions != 0 %}
                                                {{ trans_choice('firefly.recurring_repeats_x_times', rt.nr_of_repetitions) }}.
                                            @endif
                                        </p>
                                    </td>
                                    <td>
                                        <ul>
                                            {% for rep in rt.repetitions %}
                                                {% for occ in rep.occurrences %}
                                                    {% if loop.index0 < 2 %}
                                                        <li>{{ occ.isoFormat($monthAndDayFormat) }}</li>
                                                    @endif
                                                @endforeach
                                            @endforeach
                                        </ul>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                        <div class="pl-2">
                            {{ paginator.links('pagination.bootstrap-4')|raw }}
                        </div>
                    </div>
                    <div class="card-footer">
                        <a href="{{ route('recurring.create') }}" class="btn btn-success"><span class="bi bi-plus-circle"></span> {{ ('make_new_recurring')|_ }}</a>
                    </div>
                </div>
            </div>
        </div>
    @endif
    {% if total == 0 and page == 1 %}
        {% include 'partials.empty' with {objectType: 'default', type: 'recurring',route: route('recurring.create')} %}
    @endif
@endsection

@section('styles')
    <link rel="stylesheet" href="v1/css/bootstrap-sortable.css?v={{ $FF_BUILD_TIME }}" type="text/css" media="all" nonce="{{ $JS_NONCE }}">
@endsection

@section('scripts')
    <script type="text/javascript" src="v1/js/lib/bootstrap-sortable.js?v={{ $FF_BUILD_TIME }}" nonce="{{ $JS_NONCE }}"></script>
@endsection
