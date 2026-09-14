<div class="row mb-3" id="{{ $name }}_holder">
    <div class="input-group has-validation">
        <label for="{{ $options['id'] }}" class="col-sm-3 col-form-label has-validation">{{ $label }}</label>
        <div class="col-sm-9">
            <div class="input-group mb-3">
            <button class="btn btn-outline-secondary dropdown-toggle currency-dropdown" id="currency_dropdown_{{ $name }}" type="button" data-bs-toggle="dropdown" aria-expanded="false"><span id="currency_select_symbol_{{ $name }}">{{ $primaryCurrency->symbol }}</span></button>
            <ul class="dropdown-menu currency-dropdown-menu">
                @foreach($currencies as $currency)
                    <li>
                        <a href="#" class="dropdown-item currency-option"
                           data-id="{{ $currency->id }}"
                           data-name="{{ $name }}"
                           data-currency="{{ $currency->code }}"
                           data-symbol="{{ $currency->symbol }}">{{ $currency->name }}</a></li>
                @endforeach
            </ul>
            {!! Html::input('number', $name, $value)->class('form-control')->attribute('step','any') !!}
            </div>
        </div>
    </div>
    <input type="hidden" name="amount_currency_id_{{ $name }}" value="{{ $primaryCurrency->id }}"/>
</div>

