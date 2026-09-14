var currencyInfo = [];
@foreach($currencies as $id => $currency)
    currencyInfo[{{ $id }}] = {name: "{{ $currency->name }}", symbol: "{{ $currency->symbol }}", code: "{{ $currency->code }}"};
@endforeach
