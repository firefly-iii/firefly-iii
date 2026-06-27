<table class="table table-hover sortable">
    <thead>
    <tr>
        <th data-defaultsign="az">{{ trans('form.name') }}</th>
        <th data-defaultsign="_19" class="text-right hidden-xs">{{ trans('form.amount_min') }}</th>
        <th data-defaultsign="_19" class="text-right hidden-xs">{{ trans('form.amount_max') }}</th>
        <th data-defaultsign="_19">{{ trans('form.expected_on') }}</th>
        <th data-defaultsign="_19" class="text-end">{{ trans('form.paid') }}</th>
    </tr>
    </thead>
    <tbody>
    @foreach($report['bills'] as $bill)
        @if((count($bill['expected_dates']) > 0 || count($bill['paid_moments'])) > 0 && $bill['active'])
            <tr>
                <td>
                    <a href="{{ route('subscriptions.show', [$bill['id']]) }}">{{ $bill['name'] }}</a>
                </td>
                <td class="hidden-xs text-right">
                    {!! format_amount_by_symbol($bill['amount_min'], $bill['currency_symbol'], $bill['currency_decimal_places']) !!}
                </td>
                <td class="hidden-xs text-right">
                    {!! format_amount_by_symbol($bill['amount_max'], $bill['currency_symbol'], $bill['currency_decimal_places']) !!}
                </td>
                <td data-value="{{ $bill['expected_dates'][0]?->format('Y-m-d') }}">
                    @foreach($bill['expected_dates'] as $date)
                        {{ $date->isoFormat($monthAndDayFormat) }}<br/>
                    @endforeach
                </td>
                <td class="text-end">
                    @php
                        $hitCount = 0;
                    @endphp
                    @foreach($bill['paid_moments'] as $journals)
                        @foreach($journals as $journal)
                            @php
                                $hitCount++;
                            @endphp
                            <a title="{{ $journal['date']->isoFormat($monthAndDayFormat) }}"
                               href="{{ route('transactions.show', [$journal['transaction_group_id']]) }}">{{ $journal['description'] }}</a>,
                            {!! format_amount_by_symbol($journal['amount'], $journal['currency_symbol'], $journal['currency_decimal_places']) !!}
                            <br/>
                        @endforeach
                    @endforeach
                    @if(0 === $hitCount)
                        <em>{{ __('firefly.notCharged') }}</em>
                    @endif
                </td>
            </tr>
        @endif
    @endforeach

    
    @foreach($bills->getBills() as $line)
        <tr>
            <td data-value="{{ $line->getBill()->name }}">
                <a href="{{ route('subscriptions.show',$line->getBill()->id) }}">{{ $line->getBill()->name }}</a>
                <small class="text-muted"><br/>
                    {{ trans('firefly.bill_expected_between', ['start' => $line->getPayDate()->isoFormat($monthAndDayFormat), 'end' => $line->getEndOfPayDate()->isoFormat($monthAndDayFormat)]) }}
                </small>
            </td>
            <td class="text-right hidden-xs" data-value="{{ $line->getMin() }}">{!! format_amount_by_currency($line->getCurrency(), $line->getMin())  !!}</td>
            <td class="text-right hidden-xs" data-value="{{ $line->getMax() }}">{!! format_amount_by_currency($line->getCurrency(), $line->getMax())  !!}</td>

            {{-- if bill is hit, show hit amount --}}
            @if($line->isHit())
                <td data-value="{{ $line->getAmount() }}" class="text-end">
                    <a href="{{ route('transactions.show', $line->getTransactionJournalId()) }}">
                        {!! format_amount_by_currency($line->getCurrency(), $line->getAmount())  !!}
                    </a>
                </td>
            @endif
            {{-- if not but is active, show "not yet charged --}}
            @if(!$line->isHit() && $line->isActive())
                <td data-value="0" class="bg-success">{{ __('firefly.notCharged') }}</td>
            @endif
            @if(!$line->isActive() && !$line->isHit())
                <td data-value="-1">&nbsp;</td>
            @endif
            <td data-value="{{ ($line->getMax() - $line->getAmount()) }}" class="text-right hidden-xs">
                @if($line->isHit())
                    {!! format_amount_by_currency($line->getCurrency(), ($line->getMax() + $line->getAmount())) !!}
                @endif
            </td>
        </tr>

    @endforeach
    </tbody>
</table>
