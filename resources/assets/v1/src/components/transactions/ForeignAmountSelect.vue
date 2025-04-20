<!--
  - ForeignAmountSelect.vue
  - Copyright (c) 2019 james@firefly-iii.org
  -
  - This file is part of Firefly III (https://github.com/firefly-iii).
  -
  - This program is free software: you can redistribute it and/or modify
  - it under the terms of the GNU Affero General Public License as
  - published by the Free Software Foundation, either version 3 of the
  - License, or (at your option) any later version.
  -
  - This program is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU Affero General Public License for more details.
  -
  - You should have received a copy of the GNU Affero General Public License
  - along with this program.  If not, see <https://www.gnu.org/licenses/>.
  -->

<template>
    <!--
    Show if:
    - one or more currencies.
    -->
    <div v-if="this.enabledCurrencies.length >= 1" class="form-group" v-bind:class="{ 'has-error': hasError()}">
        <div class="col-sm-8 col-sm-offset-4 text-sm">
            {{ $t('form.foreign_amount') }}
        </div>
        <div class="col-sm-4">
            <select ref="currency_select" class="form-control" name="foreign_currency[]" @input="handleInput">
                <option
                    v-for="currency in this.enabledCurrencies"
                    :label="currency.attributes.name"
                    :selected="parseInt(value.currency_id) === parseInt(currency.id)"
                    :value="currency.id"

                >
                    {{ currency.attributes.name }}
                </option>
            </select>
        </div>
        <div class="col-sm-8">
            <div class="input-group">
                <input v-if="this.enabledCurrencies.length > 0" ref="amount" :placeholder="this.title"
                       :title="this.title" :value="value.amount" autocomplete="off"
                       class="form-control" name="foreign_amount[]"
                       step="any" type="number" @input="handleInput">
                <span class="input-group-btn">
                <button
                    class="btn btn-default"
                    tabIndex="-1"
                    type="button"
                    v-on:click="clearAmount"><i class="fa fa-trash-o"></i></button>
                </span>
            </div>
            <ul v-for="error in this.error" class="list-unstyled">
                <li class="text-danger">{{ error }}</li>
            </ul>
        </div>
    </div>
</template>

<script>
export default {
    name: "ForeignAmountSelect",

    props: ['source', 'destination', 'transactionType', 'value', 'error', 'no_currency', 'title',],
    mounted() {
        this.liability = false;
        // console.log('I am mounted with a ' + this.transactionType + ' transaction type and currency id!');
        // console.log(this.value);
        this.loadCurrencies();
    },
    data() {
        return {
            currencies: [],
            enabledCurrencies: [],
            exclude: null,
            // liability overrules the drop-down list if the source or dest is a liability
            liability: false
        }
    },
    watch: {
        source: function () {
            // console.log('ForeignAmountSelect watch source');
            this.changeData();
        },
        destination: function () {
            // console.log('ForeignAmountSelect watch destination');
            this.changeData();
        },
        transactionType: function () {
            // console.log('ForeignAmountSelect watch transaction type (is now ' + this.transactionType + ')');
            this.changeData();
        }
    },
    methods: {
        clearAmount: function () {
            this.$refs.amount.value = '';
            this.$emit('input', this.$refs.amount.value);
            // some event?
            this.$emit('clear:amount')
        },
        hasError: function () {
            //console.log('ForeignAmountSelect hasError');
            return this.error.length > 0;
        },
        handleInput(e) {
            // console.log('ForeignAmountSelect handleInput');
            let obj = {
                amount: this.$refs.amount.value,
                currency_id: this.$refs.currency_select.value,
            };
            // console.log(obj);
            this.$emit('input', obj
            );
        },
        changeData: function () {
            // console.log('ForeignAmountSelect changeData');
            this.enabledCurrencies = [];
            let destType = this.destination.type ? this.destination.type.toLowerCase() : 'invalid';
            let srcType = this.source.type ? this.source.type.toLowerCase() : 'invalid';
            let tType = this.transactionType ? this.transactionType.toLowerCase() : 'invalid';
            let liabilities = ['loan', 'debt', 'mortgage'];
            let sourceIsLiability = liabilities.indexOf(srcType) !== -1;
            let destIsLiability = liabilities.indexOf(destType) !== -1;

            // console.log(srcType + ' (source) is a liability: ' + sourceIsLiability);
            // console.log(destType + ' (dest) is a liability: ' + destIsLiability);
            // console.log('tType: ' + tType);
            if (tType === 'transfer' || destIsLiability || sourceIsLiability) {
                // console.log('Source is liability OR dest is liability, OR transfer. Lock list on currency of destination.');
                // console.log('Length of currencies is ' + this.currencies.length);
                // console.log(this.currencies);
                this.liability = true;
                // lock dropdown list on currencyID of destination.
                for (const key in this.currencies) {
                    if (this.currencies.hasOwnProperty(key) && /^0$|^[1-9]\d*$/.test(key) && key <= 4294967294) {
                        if (
                            parseInt(this.currencies[key].id) === parseInt(this.destination.currency_id)
                        ) {
                            // console.log('Enable currency!!');
                            // console.log(this.destination);
                            // console.log(this.currencies[key]);
                            this.enabledCurrencies.push(this.currencies[key]);
                        }
                    }
                }
                // console.log('Enabled currencies length is now ' + this.enabledCurrencies.length);
                return;
            }

            // if type is withdrawal, list all but skip the source account ID.
            if (tType === 'withdrawal' && this.source && false === sourceIsLiability) {
                for (const key in this.currencies) {
                    if (this.currencies.hasOwnProperty(key) && /^0$|^[1-9]\d*$/.test(key) && key <= 4294967294) {
                        if (this.source.currency_id !== this.currencies[key].id) {
                            this.enabledCurrencies.push(this.currencies[key]);
                        }
                    }
                }
                return;
            }

            // if type is deposit, list all but skip the source account ID.
            if (tType === 'deposit' && this.destination) {
                for (const key in this.currencies) {
                    if (this.currencies.hasOwnProperty(key) && /^0$|^[1-9]\d*$/.test(key) && key <= 4294967294) {
                        if (this.destination.currency_id !== this.currencies[key].id) {
                            this.enabledCurrencies.push(this.currencies[key]);
                        }
                    }
                }
                return;
            }
            for (const key in this.currencies) {
                if (this.currencies.hasOwnProperty(key) && /^0$|^[1-9]\d*$/.test(key) && key <= 4294967294) {
                    this.enabledCurrencies.push(this.currencies[key]);
                }
            }
        },
        loadCurrencies: function () {
            // console.log('loadCurrencies');
            // reset list of currencies:
            this.currencies = [
                {
                    id: 0,
                    attributes: {
                        name: this.no_currency,
                        enabled: true
                    },
                }
            ];

            this.enabledCurrencies = [
                {
                    attributes: {
                        name: this.no_currency,
                        enabled: true
                    },
                    id: 0,
                }
            ];

            this.getCurrencies(1);
        },
        getCurrencies: function (page) {
            let url = document.getElementsByTagName('base')[0].href + "api/v1/currencies?page=" + page;
            axios.get(url, {}).then((res) => {

                for (const key in res.data.data) {
                    if (res.data.data.hasOwnProperty(key) && /^0$|^[1-9]\d*$/.test(key) && key <= 4294967294) {
                        if (res.data.data[key].attributes.enabled) {
                            // console.log(res.data.data[key].attributes);
                            this.currencies.push(res.data.data[key]);
                            this.enabledCurrencies.push(res.data.data[key]);
                        }
                    }
                }
                if (res.data.meta.pagination.current_page < res.data.meta.pagination.total_pages) {
                    this.getCurrencies(res.data.meta.pagination.current_page + 1);
                    return;
                }
                this.changeData();
            });
        }
    }
}
</script>
