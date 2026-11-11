<table class="table table-valign-middle table-sm table-hover sortable">
    <thead>
    <tr>
        <th data-defaultsign="az">{{ __('firefly.tag') }}</th>
        <th data-defaultsign="_19" class="text-end">{{ __('firefly.spent') }}</th>
        <th data-defaultsign="_19">{{ __('firefly.transaction_count') }}</th>
    </tr>
    </thead>
    <tbody>
    @foreach($result as $row)
        <tr>
        <td data-value="{{ $row['tag_name'] }} ({{ $row['currency_name'] }})">
            @if(0 === $row['tag_id'])
                {{ $row['tag_name'] }} ({{ $row['currency_name'] }})
            @else
                <a href="{{ route('tags.show', [$row['tag_id']]) }}" title="{{ $row['tag_name'] }}">{{ $row['tag_name'] }} ({{ $row['currency_name'] }})</a>
            @endif
        </td>
        <td data-value="{{ $row['sum'] }}" class="text-end">
            {!! format_amount_by_symbol($row['sum'], $row['currency_symbol'], $row['currency_decimal_places']) !!}
        </td>

        <td data-value="{{ $row['transactions'] }}">
            {{ $row['transactions'] }}
        </td>

        </tr>
    @endforeach
    </tbody>
    <tfoot>
    @foreach($sums as $sum)
        <tr>
            <td>{{ __('firefly.sum') }} ({{ $sum['currency_name'] }})</td>
            <td class="text-end">
                {!! format_amount_by_symbol($sum['spent_sum'], $sum['currency_symbol'], $sum['currency_decimal_places']) !!}
            </td>
            <td></td>
        </tr>
    @endforeach
    <tr>
        <td colspan="3">
            <p class="text-info">
                <em>{{ __('firefly.tag_report_related_tags_listed_twice') }}</em>
            </p>
        </td>
    </tr>
    </tfoot>
</table>
