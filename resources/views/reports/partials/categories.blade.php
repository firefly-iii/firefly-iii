<table class="table table-hover sortable">
    <thead>
    <tr>
        <th data-defaultsign="az">{{ __('firefly.category') }}</th>
        <th data-defaultsign="_19" class="text-end">{{ __('firefly.spent') }}</th>
        <th data-defaultsign="_19" class="text-end">{{ __('firefly.earned') }}</th>
        <th data-defaultsign="_19" class="text-end">{{ __('firefly.sum') }}</th>
        <th data-defaultsort="disabled">&nbsp;</th>
    </tr>
    </thead>
    <tbody>
    @foreach($report['categories'] as $category)
        @if($loop->index > $listLength)
            <tr class="overListLength">
        @else
            <tr>
        @endif
        <td data-value="{{ $category['title'] }}">
            <a href="{{ route('categories.show', $category['id']) }}">{{ $category['title'] }}</a>
        </td>
        <td data-value="{{ $category['spent'] }}" class="text-end">{!! format_amount_by_symbol($category['spent'], $category['currency_symbol'], $category['currency_decimal_places'], true) !!}</td>
        <td data-value="{{ $category['earned'] }}" class="text-end">{!! format_amount_by_symbol($category['earned'], $category['currency_symbol'], $category['currency_decimal_places'], true) !!}</td>
        <td data-value="{{ $category['sum'] }}" class="text-end">{!! format_amount_by_symbol($category['sum'], $category['currency_symbol'], $category['currency_decimal_places'], true) !!}</td>
        <td class="twenty-px">
            <span class="bi bi-info-circle text-muted firefly-info-button" data-location="category-entry" data-category-id="{{ $category['id'] }}" data-currency-id="{{ $category['currency_id'] }}"
            ></span>
        </td>
    </tr>
    @endforeach
    </tbody>
    <tfoot>
    @if(count($report['categories']) > $listLength)
        <tr>
            <td colspan="4" class="active">
                <a href="#" class="listLengthTrigger">{{ trans('firefly.show_full_list',['number' => $incomeTopLength]) }}</a>
            </td>
        </tr>
    @endif
    @foreach($report['sums'] as $sum)
    <tr>
        <td><em>{{ __('firefly.sum') }} ({{ $sum['currency_name'] }})</em></td>
        <td class="text-end">
            {!! format_amount_by_symbol($sum['spent'], $sum['currency_symbol'], $sum['currency_decimal_places']) !!}
        </td>
        <td class="text-end">
            {!! format_amount_by_symbol($sum['earned'], $sum['currency_symbol'], $sum['currency_decimal_places']) !!}
        </td>
        <td class="text-end">
            {!! format_amount_by_symbol($sum['sum'], $sum['currency_symbol'], $sum['currency_decimal_places']) !!}
        </td>
    </tr>
    @endforeach
    </tfoot>
</table>
