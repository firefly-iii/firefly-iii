@extends('layout.v3.session')
@section('content')
<div class="row">
    <!-- basic info -->
    <div class="col-lg-8 col-md-12 col-sm-12">
        <div class="card mb-2">
            <div class="card-header">
                <h3 class="card-title">
                    {{ $array['title'] }}

                    ({{ $array['type'] }})

                    @if(false === $array['active'])
                        ({{ strtolower(__('firefly.inactive')) }})
                    @endif
                </h3>
            </div>
            <div class="card-body">
                <h4>{{ __('firefly.transaction_journal_meta') }}</h4>
                @if($array['nr_of_repetitions'] > 0)
                <p>
                    @if($array['journal_count'] >= $array['nr_of_repetitions'])
                        <span class="text-danger">{{ trans('firefly.recurrence_max_count', ['count' => $array['journal_count'], 'max' => $array['nr_of_repetitions']]) }}</span>
                    @endif
                    @if($array['journal_count'] < $array['nr_of_repetitions'])
                        {{ trans('firefly.recurrence_max_count', ['count' => $array['journal_count'], 'max' => $array['nr_of_repetitions']]) }}
                    @endif
                </p>
                @endif

                <p>{{ __('firefly.description') }}: <em>{{ $array['description'] }}</em></p>

                @if(false === $array['active'])
                    <p>
                        {{ __('firefly.recurrence_is_inactive') }}

                    </p>
                @endif

                <ul>
                    @foreach($array['repetitions'] as $rep)
                        <li>{{ $rep['description'] }}</li>
                    @endforeach
                </ul>
                <h4>{{ __('firefly.attachments') }}</h4>
                <x-lists.attachments :attachments="$array['attachments']" />
            </div>
            <div class="card-footer">
                <div class="btn-group">
                    <a href="{{ route('recurring.edit', [$array['id']]) }}?_from={{ urlencode($FF3_FROM) }}" class="btn btn-sm btn-outline-secondary"><span
                            class="bi bi-pencil"></span> {{ __('firefly.edit') }}</a>
                    <a href="{{ route('recurring.delete', [$array['id']]) }}?_from={{ urlencode($FF3_FROM) }}" class="btn btn-sm btn-danger">{{ __('firefly.delete') }}
                        <span class="bi bi-trash"></span></a>
                </div>
            </div>
        </div>
    </div>
    <!-- next and previous repetitions -->
    <div class="col-lg-4 col-md-12 col-sm-12">
        <div class="card mb-2">
            <div class="card-header">
                <h3 class="card-title">
                    {{ __('firefly.expected_' . $array['type'] . 's') }}
                </h3>
            </div>
            <div class="card-body">
                @if(null !== $array['repeat_until'] && now() > $array['repeat_until'])

                    <span class="text-danger">
                                            {{ trans('firefly.repeat_until_in_past', ['date' => $array['repeat_until']->isoFormat($monthAndDayFormat)]) }}
                                        </span>
                @endif
                @foreach($array['repetitions'] as $rep)
                    <p>
                        <strong>{{ $rep['description'] }}
                            @if($rep['skip'] == 1)
                                ({{ strtolower(trans('firefly.recurring_skips_one')) }})
                            @endif
                            @if($rep['skip'] > 1)
                                ({{ strtolower(trans('firefly.recurring_skips_more', ['count' => $rep['skip']])) }})
                            @endif
                        </strong>
                    </p>
                    <table class="table" aria-label="Table">
                        <tbody>
                        @foreach($rep['occurrences'] as $occ)
                            <tr>
                                <th scope="row">{{ $occ['date']->isoFormat(trans('config.month_and_date_day_js')) }}</th>
                                <td>
                                    @if(!$occ['fired'])
                                        <form action="{{ route('recurring.trigger', [$recurrence['id']]) }}" method="post"
                                              class="inline">
                                            <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                            <input type="hidden" name="date"
                                                   value="{{ $occ['date']->isoFormat('YYYY-MM-DD') }}">
                                            <input type="submit" name="submit" value="{{ __('firefly.create_right_now') }}"
                                                   class="btn btn-sm btn-outline-secondary">
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                @endforeach
            </div>
            <div class="card-footer">
                <small>
                    <em>{{ __('firefly.warning_duplicates_repetitions') }}</em>
                </small>
            </div>
        </div>
    </div>
</div>
<div class="row">
    <!-- transactions -->
    <div class="col-lg-12 col-md-12 col-sm-12">
        <div class="card mb-2">
            <div class="card-header">
                <h3 class="card-title">
                    {{ __('firefly.transaction_data') }}
                </h3>
            </div>
            <div class="card-body p-0">
                <table class="table table-hover sortable">
                    <thead>
                    <tr>
                        <th data-defaultsign="az">{{ trans('list.description') }}</th>
                        <th data-defaultsign="az">{{ trans('list.source') }}</th>
                        <th data-defaultsign="az">{{ trans('list.destination') }}</th>
                        <th data-defaultsign="_19">{{ trans('list.amount') }}</th>
                        <th data-defaultsign="az">{{ trans('list.category') }}</th>
                        <th data-defaultsign="az">{{ trans('list.budget') }}</th>
                        <th>{{ trans('list.other_meta_data') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($array['transactions'] as $transaction)
                        <tr>
                            <td data-value="{{ $transaction['description'] }}">
                                {{ $transaction['description'] }}
                            </td>
                            <td data-value="{{ $transaction['source_name'] }}">
                                <a href="{{ route('accounts.show', [$transaction['source_id']]) }}">{{ $transaction['source_name'] }}</a>
                            </td>
                            <td data-value="{{ $transaction['destination_name'] }}">
                                <a href="{{ route('accounts.show', [$transaction['destination_id']]) }}">{{ $transaction['destination_name'] }}</a>
                            </td>
                            <td>
                                {!! format_amount_by_symbol($transaction['amount'],$transaction['currency_symbol'],$transaction['currency_decimal_places']) !!}
                                @if(null != $transaction['foreign_amount'])
                                    ({!! format_amount_by_symbol($transaction['foreign_amount'],$transaction['foreign_currency_symbol'],$transaction['foreign_currency_decimal_places']) !!})
                                @endif
                            </td>
                            <td data-value="{{ $transaction['category_id'] ?? 0 }}">
                                @if('' != $transaction['category_name'])
                                    <a href="{{ route('categories.show', [$transaction['category_id']]) }}">
                                        {{ $transaction['category_name'] }}
                                    </a>
                                @endif
                            </td>
                            <td data-value="{{ $transaction['budget_id'] ?? 0 }}">
                                @if('' != $transaction['budget_name'])
                                    <a href="{{ route('budgets.show', [$transaction['budget_id']]) }}">
                                        {{ $transaction['budget_name'] }}
                                    </a>
                                @endif
                            </td>
                            <td>
                                @if(count($transaction['tags']) > 0)
                                    <p>
                                        @foreach($transaction['tags'] as $tag)
                                            <span class="badge text-bg-success">{{ $tag }}</span>
                                        @endforeach
                                    </p>
                                @endif
                                @if(0 != $transaction['piggy_bank_id'])
                                    <p>
                                        <a href="{{ route('piggy-banks.show', [$transaction['piggy_bank_id']]) }}">{{ $transaction['piggy_bank_name'] }}</a>
                                    </p>
                                @endif
                                @if(0 != $transaction['subscription_id'])
                                    <p>
                                        <a href="{{ route('subscriptions.show', [$transaction['subscription_id']]) }}">{{ $transaction['subscription_name'] }}</a>
                                    </p>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="row">
        <!-- meta data -->
        <div class="col-lg-12 col-md-12 col-sm-12">
            <div class="card mb-2">
                <div class="card-header">
                    <h3 class="card-title">
                        {{ __('firefly.created_' . $array['type'] . 's') }}
                    </h3>
                </div>
                <div class="card-body">
                    <x-lists.groups-large :groups="$groups" />
                </div>
            </div>
        </div>
    </div>
    @endsection

    @section('styles')
        <link rel="stylesheet" href="v1/css/bootstrap-sortable.css?v={{ $FF_BUILD_TIME }}" type="text/css" media="all"
              nonce="{{ $JS_NONCE }}">
    @endsection

    @section('scripts')
        <script type="text/javascript" src="v1/js/lib/bootstrap-sortable.js?v={{ $FF_BUILD_TIME }}"
                nonce="{{ $JS_NONCE }}"></script>
        {{-- required for groups.twig --}}
        <script type="text/javascript" src="v1/js/ff/list/groups.js?v={{ $FF_BUILD_TIME }}" nonce="{{ $JS_NONCE }}"></script>
    @endsection
