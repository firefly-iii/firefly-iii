@extends('layout.v3.session')
@section('content')
    <div class="row">
        <div class="col-lg-6 col-sm-12 col-md-12">
            <div class="card mb-2" id="billInfo">
                <div class="card-header">
                    <div class="row">
                        <div class="col">
                            <h3 class="card-title">{{ $object['data']['name'] }}</h3>
                        </div>
                        <div class="col text-end">
                            <button class="btn btn-sm btn-secondary-outline dropdown-toggle" type="button" id="card_header_menu" data-bs-toggle="dropdown" aria-expanded="false">
                                    <span class="bi bi-list"></span></button>
                            <ul class="dropdown-menu" aria-labelledby="card_header_menu">
                                        <li><a class="dropdown-item" href="{{ route('subscriptions.edit', $object['data']['id']) }}"><span class="bi bi-pencil"></span> {{ __('firefly.edit') }}</a></li>
                                        <li><a class="dropdown-item" href="{{ route('subscriptions.delete', $object['data']['id']) }}"><span class="bi bi-trash"></span> {{ __('firefly.delete') }}</a></li>
                                    </ul>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <table class="table table-striped">
                        <tr>
                            <td colspan="2">
                                @php
                                    $lowAmount  = format_amount_by_currency($object['data']['currency'],$object['data']['amount_min']);
                                    $highAmount = format_amount_by_currency($object['data']['currency'],$object['data']['amount_max']);
                                @endphp
                                @if(null !== $object['data']['pc_amount_min']))
                                    @php
                                        $lowAmount = $lowAmount . ' (' . format_amount_by_code($object['data']['pc_amount_min'], $primaryCurrency->code) . ')';
                                    @endphp
                                @endif
                                @if(null !== $object['data']['pc_amount_max']))
                                    @php
                                        $highAmount = $highAmount . ' (' . format_amount_by_code($object['data']['pc_amount_max'], $primaryCurrency->code) . ')';
                                    @endphp
                                @endif
                                {!! trans('firefly.match_between_amounts', ['low' => $lowAmount, 'high' => $highAmount]) !!}
                                {{ __('firefly.repeats') }}
                                {{ trans('firefly.repeat_freq_'  . $object['data']['repeat_freq']) }}.
                            </td>
                        </tr>

                        <tr>
                            <td class="half">{{ __('firefly.bill_is_active') }}</td>
                            <td>
                                @if($object['data']['active'])
                                    <span class="bi bi-check" title="{{ __('firefly.active') }}"></span> {{ __('firefly.yes') }}
                                @else
                                    <span class="bi bi-x" title="{{ __('firefly.inactive') }}"></span> {{ __('firefly.no') }}
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td>{{ __('firefly.next_expected_match') }}</td>
                            <td>

                                @if(count($object['data']['pay_dates']) > 0)
                                    {{ new \Carbon\Carbon($object['data']['pay_dates'][0])->isoFormat($monthAndDayFormat) }}
                                @else
                                    {{ __('firefly.unknown') }}
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td>{{ trans('firefly.average_bill_amount_year', ['year' => $year]) }}</td>
                            <td>
                                @foreach($yearAverage as $avg)
                                    {!! format_amount_by_symbol($avg['avg'], $avg['currency_symbol'], $avg['currency_decimal_places'], true) !!}
                                @if($convertToPrimary && 0 !== $ag['pc_avg'])
                                    ({!! format_amount_by_symbol($avg['pc_avg'], $primaryCurrency->symbol, $primaryCurrency->decimal_places, true)  !!})
                                @endif
                                <br>
                                @endforeach
                            </td>
                        </tr>
                        <tr>
                            <td>{{ __('firefly.average_bill_amount_overall') }}</td>
                            <td>
                                @foreach($overallAverage as $avg)
                                    {!! format_amount_by_symbol($avg['avg'], $avg['currency_symbol'], $avg['currency_decimal_places'], true) !!}
                                    @if($convertToPrimary && 0 !== $ag['pc_avg'])
                                        ({!! format_amount_by_symbol($avg['pc_avg'], $primaryCurrency->symbol, $primaryCurrency->decimal_places, true) !!})
                                    @endif
                                    <br>
                                @endforeach
                            </td>
                        </tr>
                    </table>
                </div>
                <div class="card-footer">
                    <div class="btn-group">
                        <a class="btn btn-outline-secondary" href="{{ route('subscriptions.edit', [$object['data']['id']]) }}">{{ __('firefly.edit') }}</a>
                        <a class="btn btn-danger" href="{{ route('subscriptions.delete', [$object['data']['id']]) }}">{{ __('firefly.delete') }}</a>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6 col-sm-12 col-md-12">
            <div class="card mb-2">
                <div class="card-header">
                    <h3 class="card-title">{{ __('firefly.bill_related_rules') }}</h3>
                </div>
                <div class="card-body">
                    @if($rules->count() > 0)
                        <ul>
                            @foreach($rules as $rule)
                                <li><a href="{{ route('rules.edit', [$rule->id]) }}">{{ $rule->title }}</a>
                                    @if(!$rule->active)({{ strtolower(__('firefly.list_inactive_rule')) }})@endif
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
                <div class="card-footer">
                    <form action="{{ route('subscriptions.rescan',$object['data']['id']) }}" method="post">
                        <input type="hidden" name="_token" value="{{ csrf_token() }}"/>
                        <p>
                            <input type="submit" name="submit" value="{{ __('firefly.rescan_old') }}" class="btn btn-outline-secondary"/>
                        </p>
                    </form>

                    <p><small class="text-muted">
                            {{ __('firefly.running_again_loss') }}
                        </small>

                    </p>
                </div>
            </div>
            @if($object['data']['notes'])
                <div class="card mb-2">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('firefly.notes') }}</h3>
                    </div>
                    <div class="card-body">
                        {{parse_markdown($object['data']['notes']) }}
                    </div>
                </div>
            @endif

            @if($attachments->count() > 0)
                <div class="card mb-2">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('firefly.attachments') }}</h3>
                    </div>
                    <div class="card-body p-0">
                        {% include 'list.attachments' %}
                    </div>
                </div>
            @endif
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12 col-sm-12 col-md-12">
            <div class="card mb-2" id="billChart">
                <div class="card-header">
                    <h3 class="card-title">{{ __('firefly.chart') }}</h3>
                </div>
                <div class="card-body">
                    <canvas id="bill-overview" class="wide-chart" height="400" width="100%"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12 col-sm-12 col-md-12">
            <div class="card mb-2">
                <div class="card-header">
                    <h3 class="card-title">{{ __('firefly.connected_journals') }}</h3>
                </div>
                <div class="card-body">
                    <x-lists.groups-large :groups="$groups" />
                </div>
            </div>
        </div>
    </div>

@endsection

@section('scripts')
    @vite(['js/pages/generic.js'])
    <script type="text/javascript" nonce="{{ $JS_NONCE }}">
        var billCurrencySymbol = "{{ $convertToPrimary ? $primaryCurrency->symbol : $object['data']['currency']['symbol'] }}";
        var billUrl = '{{ route('chart.bill.single', [$object['data']['id']]) }}';
    </script>
    <script type="text/javascript" src="v1/js/lib/Chart.bundle.min.js?v={{ $FF_BUILD_TIME }}" nonce="{{ $JS_NONCE }}"></script>
    <script type="text/javascript" src="v1/js/ff/charts.defaults.js?v={{ $FF_BUILD_TIME }}" nonce="{{ $JS_NONCE }}"></script>
    <script type="text/javascript" src="v1/js/ff/charts.js?v={{ $FF_BUILD_TIME }}" nonce="{{ $JS_NONCE }}"></script>
    <script type="text/javascript" src="v1/js/ff/bills/show.js?v={{ $FF_BUILD_TIME }}" nonce="{{ $JS_NONCE }}"></script>
    {{-- required for groups.twig --}}
    <script type="text/javascript" src="v1/js/ff/list/groups.js?v={{ $FF_BUILD_TIME }}" nonce="{{ $JS_NONCE }}"></script>
@endsection
