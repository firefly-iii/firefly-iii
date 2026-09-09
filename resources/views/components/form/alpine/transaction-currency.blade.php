<div class="mb-3">
    <div class="row">
        <label for="form_transaction_currency_id" class="col-sm-3 col-form-label has-validation">{{ __('form.transaction_currency_id') }}</label>
        <div class="col-sm-9">
            <div class="input-group has-validation">
                <select id="form_transaction_currency_id"
                       ref="transaction_currency_id"
                       x-model={{ $value }}
                       name="transaction_currency_id"
                       @input="handleInput"
                        x-bind:class="{'form-select': true, 'is-invalid': errors.currency_id.length > 0}"
                >
                    <template x-for="currency in currencies" :key="currency.id">
                        <option :value="currency.id" x-text="currency.name"></option>
                    </template>
                </select>
            </div>
        </div>
    </div>
    <template x-for="error in errors.currency_id">
        <div class="row">
            <div class="col-sm-9 offset-sm-3">
                <ul class="list-unstyled">
                    <li class="text-danger" x-text="error"></li>
                </ul>
            </div>
        </div>
    </template>
</div>
