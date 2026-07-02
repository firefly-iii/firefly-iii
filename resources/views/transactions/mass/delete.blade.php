@extends('layout.v3.session')
@section('content')
    <form method="POST" action="{{ route('transactions.mass.destroy') }}" accept-charset="UTF-8" class="form-horizontal" id="destroy">
        <input name="_token" type="hidden" value="{{ csrf_token() }}">

        <div class="row">
            <div class="col-lg-8 offset-lg-2 col-md-8 coll-offset-md-2 col-sm-12">
                <div class="card card-danger card-outline">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('firefly.mass_delete_journals') }}</h3>
                    </div>
                    <div class="card-body">
                        <p class="text-danger">
                            {{ trans('form.permDeleteWarning') }}
                            {{ __('firefly.perm-delete-many') }}
                        </p>
                        <p>
                            {{ trans('form.mass_journal_are_you_sure') }}
                            {{ trans('form.mass_make_selection') }}
                        </p>

                        <table class="table table-striped table-sm">
                            <tr>
                                <th>&nbsp;</th>
                                <th>{{ trans('list.transaction_type') }}</th>
                                <th>{{ trans('list.description') }}</th>
                                <th>{{ trans('list.amount') }}</th>
                                <th class="hidden-sm hidden-xs">{{ trans('list.date') }}</th>
                                <th class="hidden-xs">{{ trans('list.from') }}</th>
                                <th class="hidden-xs">{{ trans('list.to') }}</th>
                            </tr>
                            @foreach($journals as $journal)
                                <tr>
                                    <td>
                                        <input type="checkbox" name="confirm_mass_delete[]" value="{{ $journal['transaction_journal_id'] }}" checked/>
                                    </td>
                                    <td>
                                        @if('Withdrawal' === $journal['transaction_type_type'])
                                            <span class="bi bi-arrow-left" title="{{ trans('firefly.Withdrawal') }}"></span>
                                        @endif

                                        @if('Deposit' === $journal['transaction_type_type'])
                                            <span class="bi bi-arrow-right" title="{{ trans('firefly.Deposit') }}"></span>
                                        @endif

                                        @if('Transfer' === $journal['transaction_type_type'])
                                            <span class="bi bi-arrow-left-right" title="{{ trans('firefly.Deposit') }}"></span>
                                        @endif

                                        @if('Reconciliation' === $journal['transaction_type_type'])
                                            <span class="bi bi-calculator" title="{{ trans('firefly.reconciliation_transaction') }}"></span>
                                        @endif
                                        @if('Opening balance' === $journal['transaction_type_type'])
                                            <span class="bi bi-star" title="{{ trans('firefly.Opening balance') }}"></span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('transactions.show',$journal['transaction_group_id']) }}"
                                           title="{{ $journal['description'] }}">{{ $journal['description'] }}</a>
                                    </td>
                                    <td>
                                        @if('Deposit' === $journal['transaction_type_type'])
                                            {!! format_amount_by_symbol($journal['amount']*-1, $journal['currency_symbol'], $journal['currency_decimal_places']) !!}
                                            @if(null !== $journal['foreign_amount'])
                                                ({!! format_amount_by_symbol($journal['foreign_amount']*-1, $journal['foreign_currency_symbol'], $journal['foreign_currency_decimal_places']) !!})
                                            @endif
                                        @elseif($journal['transaction_type_type'] === 'Transfer')
                                            <span class="text-info money-transfer">{!! format_amount_by_symbol($journal['amount']*-1, $journal['currency_symbol'], $journal['currency_decimal_places'], false) !!}
                                                @if(null !== $journal['foreign_amount'])
                                                    ({!! format_amount_by_symbol($journal['foreign_amount']*-1, $journal['foreign_currency_symbol'], $journal['foreign_currency_decimal_places'], false) !!})
                                                @endif
                                            </span>
                                        @else
                                            {!! format_amount_by_symbol($journal['amount'], $journal['currency_symbol'], $journal['currency_decimal_places']) !!}
                                            @if(null !== $journal['foreign_amount'])
                                                ({!! format_amount_by_symbol($journal['foreign_amount'], $journal['foreign_currency_symbol'], $journal['foreign_currency_decimal_places']) !!})
                                            @endif
                                        @endif
                                    </td>
                                    <td>
                                        {{ $journal['date']->isoFormat($monthAndDayFormat) }}
                                    </td>
                                    <td>
                                        <a href="{{ route('accounts.show', [$journal['source_account_id']]) }}"
                                           title="{{ $journal['source_account_iban'] ?? $journal['source_account_name'] }}">{{ $journal['source_account_name'] }}</a>
                                    </td>
                                    <td>
                                        <a href="{{ route('accounts.show', [$journal['destination_account_id']]) }}"
                                           title="{{ $journal['destination_account_iban'] ?? $journal['destination_account_name'] }}">{{ $journal['destination_account_name'] }}</a>
                                    </td>
                                </tr>
                            @endforeach
                        </table>

                    </div>
                    <div class="card-footer text-end">
                        <input type="submit" name="submit" value="{{ trans('form.delete_all_permanently') }}" class="btn btn-danger"/>
                        <a href="{{ route('index') }}" class="btn-outline-secondary btn">{{ trans('form.cancel') }}</a>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection
@section('scripts')
    @vite(['js/pages/generic.js'])
@endsection
