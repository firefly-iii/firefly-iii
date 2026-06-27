@extends('layout.v3.session')
@section('breadcrumbs')
    {{ Breadcrumbs::render(Route::getCurrentRoute()->getName(), $accountIds, $start, $end) }}

@endsection
@section('content')
    <div class="row no-print">
        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
            <div class="card mb-2" id="optionsBox">
                <div class="card-header">
                    <h3 class="card-title">{{ __('firefly.options') }}</h3>
                </div>
                <div class="card-body">
                    <ul class="list-inline">
                        @foreach($hideable as $hide)
                            <li class="list-inline-item"><input
                                    @if(in_array($hide, $defaultShow, true)) checked @endif
                                    type="checkbox" class="fw-normal audit-option-checkbox" name="option[]" value="{{ $hide }}" id="option_{{ $hide }}"/> <label
                                    for="option_{{ $hide }}">{{ trans('list.' . $hide) }}</label></li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>

@foreach($accounts as $account)
        <div class="row">
            <div class="col-lg-12 col-md-12 col-sm-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">{{ $account->name }}</h3>
                    </div>
                    @if(!$auditData[$account->id]['exists'])
                        <div class="card-body">
                            <em>
                                {!! trans('firefly.no_audit_activity',
                                    [
                                        'account_name'=> e($account->name),
                                        'url' => route('accounts.show', [$account->id]),
                                        'start'=> $start->isoFormat($monthAndDayFormat),
                                        'end' => $end->isoFormat($monthAndDayFormat),
                                    ]) !!}

                            </em>
                        </div>
                    @else
                        <div class="card-body p-0">
                            <div class="alert alert-info m-2" role="alert">
                                {!! trans('firefly.audit_end_balance',
                                    [
                                        'account_name' => e($account->name),
                                        'url' => route('accounts.show', [$account->id]),
                                        'end' => $auditData[$account->id]['dayBefore'],
                                        'balance' => format_amount_by_account($account, $auditData[$account->id]['dayBeforeBalance']['balance'])
                                    ]) !!}
                            </div>
                            <x-report.partial.journals-audit :audit-data="$auditData" :journals="$auditData[$account->id]['journals']" :account="$account" />

                            <div class="alert alert-info m-2" role="alert">

                                {!! trans('firefly.audit_end_balance',
                                    [
                                        'account_name' => e($account->name),
                                        'url' => route('accounts.show',$account->id),
                                        'end' => $auditData[$account->id]['end'],
                                        'balance' => format_amount_by_account($account, $auditData[$account->id]['endBalance'])
                                    ]) !!}
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endforeach

@endsection
@section('styles')
@endsection
@section('scripts')
    @vite(['js/pages/generic.js'])
    <script type="text/javascript" nonce="{{ $JS_NONCE }}">
        var hideable = {!! json_encode($hideable) !!};
    </script>
    <script type="text/javascript" src="v1/js/ff/reports/all.js?v={{ $FF_BUILD_TIME }}" nonce="{{ $JS_NONCE }}"></script>
    <script type="text/javascript" src="v1/js/ff/reports/audit/all.js?v={{ $FF_BUILD_TIME }}" nonce="{{ $JS_NONCE }}"></script>
@endsection
