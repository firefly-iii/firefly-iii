<div class="card mb-4">
    <div class="card-header">
        <div class="card-title"><a href="{{ route('piggy-banks.index') }}" title="{{ __('firefly.piggyBanks') }}">{{ __('firefly.piggyBanks') }}</a></div>

    </div>
    <div class="card-body">
        @foreach($info as $entry)
        <strong>{{ $entry['name'] }}</strong><br/>
        <div class="progress">
            <div class="progress-bar progress-bar-striped w-{{ round($entry['percentage']) }}" role="progressbar" aria-valuenow="{{ $entry['percentage'] }}" aria-valuemin="0" aria-valuemax="100">
                @if($entry['percentage'] > 20)
                    @if($convertToPrimary && 0 !== $avg['pc_amount'])
                         {!! format_amount_by_symbol($entry['pc_amount'], $entry['primary_currency_symbol'], $entry['primary_currency_decimal_places'], false) }}
                        ({!! format_amount_by_symbol($entry['amount'], $entry['currency_symbol'], $entry['currency_decimal_places'], false) }})
                    @endif
                    @if(!$convertToPrimary)
                        {!! format_amount_by_symbol($entry['amount'], $entry['currency_symbol'], $entry['currency_decimal_places'], false) }}
                    @endif
                @endif
            </div>
            @if($entry['percentage'] <= 20)
            &nbsp;
                @if($convertToPrimary && 0 !== $avg['pc_amount'])
                    {!! format_amount_by_symbol($entry['pc_amount'], $entry['primary_currency_symbol'], $entry['primary_currency_decimal_places'], false) }}
                    ({!! format_amount_by_symbol($entry['amount'], $entry['currency_symbol'], $entry['currency_decimal_places'], false) }})
                @endif
                @if(!$convertToPrimary)
                    {!! format_amount_by_symbol($entry['amount'], $entry['currency_symbol'], $entry['currency_decimal_places'], false) }}
                @endif
            @endif
        </div>
        @endforeach
    </div>
    <div class="card-footer">
        <a href="{{ route('piggy-banks.index') }}" class="btn btn-primary btn-sm"><span class="bi bi-bullseye"></span> {{ __('firefly.go_to_piggies') }}</a>
    </div>
</div>
