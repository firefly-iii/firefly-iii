<div class="{{{$classes}}}">
    <label for="{{{$options['id']}}}" class="col-sm-4 control-label">{{{$label}}}</label>
    <div class="col-sm-8">
        <div class="input-group">
            <div class="input-group-btn">
                <button type="button" class="btn btn-default dropdown-toggle amountCurrencyDropdown" data-toggle="dropdown" aria-expanded="false">
                    <span id="amountCurrentSymbol">{{$defaultCurrency->symbol}}</span> <span class="caret"></span>
                </button>
                <ul class="dropdown-menu" role="menu">
                    @foreach($currencies as $currency)
                        <li><a href="#" class="currencySelect" data-id="{{{$currency->id}}}" data-field="amount" data-currency="{{{$currency->code}}}" data-symbol="{{{$currency->symbol}}}">{{{$currency->name}}}</a></li>
                    @endforeach
                </ul>
            </div>
            {{Form::input('number', $name, $value, $options)}}


        </div>
        @include('form.feedback')
    </div>
    {{Form::input('hidden','amount_currency_id',$defaultCurrency->id)}}
</div>