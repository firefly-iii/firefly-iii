@extends('layout.v3.session')
@section('content')
    <div class="row">
        <div class="col-lg-8 col-md-8 col-sm-6">
            <div class="card mb-2" id="piggyChart">
                <div class="card-header">
                    <h3 class="card-title">{{ __('firefly.events') }}</h3>
                </div>
                <div class="card-body">
                    <canvas id="piggy-bank-history" class="wide-chart" height="400" width="100%"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-4 col-sm-6">
            <div class="card mb-2">
                <div class="card-header">
                    <div class="row">
                        <div class="col">
                            <h3 class="card-title">{{ __('firefly.details') }}</h3>
                        </div>
                        <div class="col text-end">
                            <div class="dropdown">
                                <button class="btn btn-sm btn-secondary-outline dropdown-toggle" type="button" id="card_header_menu" data-bs-toggle="dropdown" aria-expanded="false">
                                    <span class="bi bi-list"></span>
                                </button>
                                <ul class="dropdown-menu" aria-labelledby="card_header_menu">
                                    <li><a class="dropdown-item" href="{{ route('piggy-banks.edit', $piggy['id']) }}"><span class="bi bi-pencil"></span> {{ __('firefly.edit') }}</a></li>
                                    <li><a class="dropdown-item" href="{{ route('piggy-banks.delete', $piggy['id']) }}"><span class="bi bi-trash"></span> {{ __('firefly.delete') }}</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <table class="table table-responsive table-hover" id="piggyDetails">
                        <tr>
                            <td class="forty">{{ __('firefly.saveOnAccounts') }}</td>
                            <td>
                                @foreach($piggy['accounts'] as $account)
                                    <a href="{{ route('accounts.show', [$account['account_id']]) }}">{{ $account['name'] }}</a><br>
                                @endforeach
                            </td>
                        </tr>
                        @if('' !== (string)$piggy['object_group_title'])
                            <tr>
                                <td class="forty">{{ __('firefly.object_group') }}</td>
                                <td>{{ $piggy['object_group_title'] }}</a></td>
                            </tr>
                        @endif
                        @if(null !== $piggy['target_amount'])
                            <tr>
                                <td>{{ __('firefly.target_amount') }}</td>
                                <td>
                                    {!! format_amount_by_symbol($piggy['target_amount'], $piggy['currency_symbol'], $piggy['currency_decimal_places']) !!}
                                </td>
                            </tr>
                        @endif
                        @foreach($piggy['accounts'] as $account)
                            <tr>
                                <td>
                                    {{ __('firefly.saved_so_far') }}
                                    (<a href="{{ route('accounts.show', $account['account_id']) }}">{{ $account['name'] }}</a>)
                                </td>
                                <td>
                                    {!! format_amount_by_symbol($account['current_amount'], $piggy['currency_symbol'], $piggy['currency_decimal_places']) !!}
                                </td>
                            </tr>

                        @endforeach
                        <tr>
                            <td>{{ __('firefly.saved_so_far_total') }}</td>
                            <td>
                                {!! format_amount_by_symbol($piggy['current_amount'], $piggy['currency_symbol'], $piggy['currency_decimal_places']) !!}
                            </td>
                        </tr>
                        @if(null !== $piggy['left_to_save'])
                            <tr>
                                <td>{{ __('firefly.left_to_save') }}</td>
                                <td>
                                    {!! format_amount_by_symbol($piggy['left_to_save'], $piggy['currency_symbol'], $piggy['currency_decimal_places']) !!}
                                </td>
                            </tr>
                        @endif
                        <tr>
                            <td>{{ __('firefly.start_date') }}</td>
                            <td>
                                @if(null !== $piggyBank->start_date)
                                    {{ $piggyBank->start_date->isoFormat($monthAndDayFormat) }}
                                @else
                                    <em>{{ __('firefly.no_start_date') }}</em>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td>{{ __('firefly.target_date') }}</td>
                            <td>
                                @if(null !== $piggyBank->target_date)
                                    {{ $piggyBank->target_date->isoFormat($monthAndDayFormat) }}
                                @else
                                    <em>{{ __('firefly.no_target_date') }}</em>
                                @endif
                            </td>
                        </tr>
                        @if(null !== $piggyBank->target_date && null !== $piggy['save_per_month'])
                            <tr>
                                <td>{{ __('firefly.suggested_amount') }}</td>
                                <td>
                                    {{ format_amount_by_symbol($piggy['save_per_month'], $piggy['currency_symbol'], $piggy['currency_decimal_places']) }}
                                </td>
                            </tr>
                        @endif
                    </table>
                </div>
            </div>
            <div class="card mb-2">
                <div class="card-header">
                    <h3 class="card-title">{{ __('firefly.event_history') }} (<a class="confirm-history-delete reset-link" href="#">{{ __('firefly.reset_history') }}</a>)</h3>
                </div>
                <div class="card-body p-0" id="piggyEvents">
                    <x-lists.piggy-bank-events :events="$events" :show-piggy-bank="false"/>
                </div>
            </div>
        </div>
    </div>
    <div class="row">

        @if('' !== (string) $piggy['notes'])
            <div class="col-lg-8 col-md-8 col-sm-12 col-xs-12">

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">{{ trans('form.notes') }}</h3>
                    </div>
                    <div class="box-body markdown">{{ parse_markdown($piggy['notes']) }}
                    </div>
                </div>

            </div>
        @endif
        @if($attachments->count() > 0)
            <div class="col-lg-8 col-md-8 col-sm-12 col-xs-12">
                <div class="card mb-2">
                    <div class="card-header">
                        <h3 class="card-title">
                            {{ __('firefly.attachments') }}
                        </h3>
                    </div>
                    <div class="card-body p-0">
                        <x-lists.attachments :attachments="$attachments" />
                    </div>
                </div>
            </div>
        @endif
    </div>
    <form id="reset-form" action="{{ route('piggy-banks.reset', [$piggyBank->id]) }}" method="POST" class="hidden">
        <input type="hidden" name="_token" value="{{ csrf_token() }}"/>
    </form>
@endsection

@section('scripts')
    @vite(['js/pages/generic.js'])
    <script type="text/javascript" nonce="{{ $JS_NONCE }}">
        var piggyBankID = {{ $piggyBank->id }};
        var confirmText= '{{__('firefly.reset_history_confirm') }}';
    </script>

    <script type="text/javascript" src="v1/js/lib/Chart.bundle.min.js?v={{ $FF_BUILD_TIME }}" nonce="{{ $JS_NONCE }}"></script>
    <script type="text/javascript" src="v1/js/ff/charts.defaults.js?v={{ $FF_BUILD_TIME }}" nonce="{{ $JS_NONCE }}"></script>
    <script type="text/javascript" src="v1/js/ff/charts.js?v={{ $FF_BUILD_TIME }}" nonce="{{ $JS_NONCE }}"></script>
    <script type="text/javascript" src="v1/js/ff/piggy-banks/show.js?v={{ $FF_BUILD_TIME }}" nonce="{{ $JS_NONCE }}"></script>
@endsection
