@extends('layout.v3.session')
@section('content')
    @if(0 === $total && 1 === $page)
        @php
            $shownDemo = true
        @endphp
        <x-empty-page :route="route('recurring.create')" type="recurring" object-type="default" />
    @endif
    @if($total > 0)
    <!-- block with list of recurring transaction -->
        <div class="row">
            <div class="col-lg-12 col-md-12 col-sm-12">
                <div class="card mb-2">
                    <x-elements.card-header-with-menu :cardTitle="trans('firefly.recurrences')" :route="route('recurring.create')" :linkTitle="__('firefly.make_new_recurring')"/>

                    <div class="card-body p-0">
                        <!-- list of recurring here -->
                        <div class="pl-2">
                            {{ $paginator->links('pagination.bootstrap-4') }}
                        </div>
                        <table class="table table-responsive  table-hover sortable">
                            <thead>
                            <tr>
                                <th class="hidden-sm hidden-xs" data-defaultsort="disabled">&nbsp;</th>
                                <th data-defaultsign="az">{{ trans('list.title') }}</th>
                                <th data-defaultsort="disabled">{{ trans('list.transaction_s') }}</th>
                                <th data-defaultsort="disabled">{{ trans('list.repetitions') }}</th>
                                <th data-defaultsign="month" data-dateformat="{{ $madMomentJS }}">{{ trans('list.next_due') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($paginator as $rt)
                                <tr>
                                    <td class="hidden-sm hidden-xs">
                                        <div class="btn-group btn-group-sm edit_tr_buttons">
                                            <a class="btn btn-outline-secondary btn-xs" title="{{ __('firefly.edit') }}" href="{{ route('recurring.edit',$rt['id']) }}"><span
                                                    class="bi bi-pencil"></span></a><a class="btn btn-danger btn-xs" title="{{ __('firefly.delete') }}"
                                                                                             href="{{ route('recurring.delete',$rt['id']) }}"><span
                                                    class="bi bi-trash"></span></a>
                                        </div>
                                    </td>
                                    <td data-value="{{ $rt['title'] }}">
                                        @if($rt['attachments'] > 0)
                                            <span class="bi bi-paperclip"></span>
                                        @endif
                                        @if(false === $rt['active'])<s>@endif
                                            {{ __('firefly.'.$rt['type']) }}:
                                            <a href="{{ route('recurring.show', $rt['id']) }}">{{ $rt['title'] }}</a>
                                            @if(false === $rt['active'])</s> ({{ strtolower(__('firefly.inactive')) }})@endif
                                        @if(strlen($rt['description']) > 0)
                                            <small><br>{{ $rt['description'] }}</small>
                                        @endif
                                    </td>
                                    <td data-value="0">
                                        <ol>
                                            @foreach($rt['transactions'] as $rtt)
                                                <li>
                                                    {{-- normal amount + comma --}}
                                                    {!! format_amount_by_symbol($rtt['amount'],$rtt['currency_symbol'],$rtt['currency_decimal_places'])  !!}@if(null === $rtt['foreign_amount']),@endif

                                                    {{-- foreign amount + comma --}}
                                                    @if('' !== (string) $rtt['foreign_amount'])
                                                        ({!! format_amount_by_symbol($rtt['foreign_amount'],$rtt['foreign_currency_symbol'],$rtt['foreign_currency_decimal_places']) !!}),
                                                    @endif
                                                    <a href="{{ route('accounts.show', $rtt['source_id']) }}">{{ $rtt['source_name'] }}</a>
                                                    &rarr;
                                                    <a href="{{ route('accounts.show', $rtt['destination_id']) }}">{{ $rtt['destination_name'] }}</a>
                                                </li>
                                            @endforeach
                                        </ol>
                                    </td>
                                    <td>
                                        @if(null !== $rt['repeat_until'] && $today > $rt['repeat_until'])
                                            <span class="text-danger">
                                            {{ trans('firefly.repeat_until_in_past', ['date' => $rt['repeat_until']->isoFormat($monthAndDayFormat)]) }}
                                        </span>
                                        @endif
                                        <ul>
                                            @foreach($rt['repetitions'] as $rep)
                                                <li>{{ $rep['description'] }}
                                                    @if(1 === $rep['skip'])
                                                        ({{ strtolower(trans('firefly.recurring_skips_one')) }}).
                                                    @endif
                                                    @if($rep['skip'] > 1)
                                                        ({{ strtolower(trans('firefly.recurring_skips_more', ['count' => $rep['repetition_skip']])) }}).
                                                    @endif
                                                    @if(3 === $rep['weekend'])
                                                        <br>{{ __('firefly.will_jump_friday') }}
                                                    @endif
                                                    @if(4 === $rep['weekend'])
                                                        <br>{{ __('firefly.will_jump_monday') }}
                                                    @endif
                                                    @if(2 === $rep['weekend'])
                                                        <br>{{ __('firefly.except_weekends') }}
                                                    @endif
                                                </li>
                                            @endforeach
                                        </ul>
                                        <p>
                                            @if(null === $rt['repeat_until'] && 0 === $rt['repetitions'])
                                                {{ __('firefly.recurring_repeats_forever') }}.
                                            @endif
                                            @if(null !== $rt['repeat_until'] && 0 === $rt['repetitions'])
                                                {{ trans('firefly.recurring_repeats_until', ['date' => $rt['repeat_until']->isoFormat($monthAndDayFormat)]) }}.
                                            @endif
                                            @if(null === $rt['repeat_until'] && 0 !== (int)$rt['nr_of_repetitions'])
                                               {{ trans_choice('firefly.recurring_repeats_x_times', $rt['nr_of_repetitions']) }}.
                                            @endif
                                        </p>
                                    </td>
                                    <td>
                                        <ul>
                                            @foreach($rt['repetitions'] as $rep)
                                                @foreach($rep['occurrences'] as $occ)
                                                    @if($loop->index < 2)
                                                        <li>{{ $occ->isoFormat($monthAndDayFormat) }}</li>
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
                            {{ $paginator->links('pagination.bootstrap-4') }}
                        </div>
                    </div>
                    <x-elements.card-footer-with-menu :route="route('recurring.create')" :linkTitle="__('firefly.make_new_recurring')" />
                </div>
            </div>
        </div>
@endif
@endsection
@section('styles')
    <link rel="stylesheet" href="v1/css/bootstrap-sortable.css?v={{ $FF_BUILD_TIME }}" type="text/css" media="all" nonce="{{ $JS_NONCE }}">
@endsection

@section('scripts')
    @vite(['js/pages/generic.js'])
    <script type="text/javascript" src="v1/js/lib/bootstrap-sortable.js?v={{ $FF_BUILD_TIME }}" nonce="{{ $JS_NONCE }}"></script>
@endsection
