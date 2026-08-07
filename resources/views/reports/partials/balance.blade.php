<table class="table table-hover">
    <thead>
    <tr>
        <th>{{ __('firefly.budgets') }}</th>
        @foreach($report['accounts'] as $account)
            @if($account['sum'] !== 0)
                <th class="text-end"><a href="{{ route('accounts.show',$account['id']) }}" title="{{ $account['iban'] }}">{{ $account['name'] }}</a></th>
            @endif
        @endforeach
        <th class="text-end">{{ __('firefly.sum') }}</th>
    </tr>
    </thead>
    <tbody>
    @foreach($report['budgets'] as $budget)
        @if(count($budget['spent']) > 0)
            <tr>
                <td>
                    <a href="{{ route('budgets.show', [$budget['budget_id']]) }}">{{ $budget['budget_name'] }}</a>
                </td>
                @foreach($report['accounts'] as $account)
                    @if(array_key_exists($account['id'], $budget['spent']))
                        @php
                        $array = $budget['spent'][$account['id']];
                        @endphp
                        <td class="text-end">
                            {!! format_amount_by_symbol($array['spent'], $array['currency_symbol'], $array['currency_decimal_places']) !!}
                            <span data-location="budget-entry"
                               data-budget-id="{{ $budget['budget_id'] }}"
                               data-account-id="{{ $account['id'] }}"
                               data-currency-id="{{ $array['currency_id'] }}"
                               class="bi bi-info-circle text-muted firefly-info-button"></span>
                        </td>
                    @else
                            <td>
                                @php
                            var_dump($report['accounts'][$account['id']]['sum'] ?? null);
                            @endphp
                            </td>
                    @endif

                @endforeach
                <td class="text-end">
                    @if(array_key_exists($budget['budget_id'], $report['sums']))
                        @foreach($report['sums'][$budget['budget_id']] as $sum)
                            {!! format_amount_by_symbol($sum['sum'], $sum['currency_symbol'], $sum['currency_decimal_places']) !!}
                            <br/>
                        @endforeach
                    @endif
                </td>
            </tr>
        @endif
    @endforeach
    </tbody>
    <tfoot>
    <tr>
        <td><em>{{ __('firefly.sum') }}</em></td>
        @foreach($report['accounts'] as $account)
            @if($account['sum'] !== '0')
                <td class="text-end">
                    {!! format_amount_by_symbol($account['sum'], $account['currency_symbol'], $account['currency_decimal_places']) !!}
                </td>
            @endif
        @endforeach
    </tr>
    </tfoot>
</table>
