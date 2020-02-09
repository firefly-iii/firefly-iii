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
    - more than one currency enabled in system.

    -->
    <div class="form-group" v-bind:class="{ 'has-error': hasError()}" v-if="
    this.enabledCurrencies.length > 1">
        <div class="col-sm-8 col-sm-offset-4 text-sm">
            {{ $t('form.foreign_amount') }}
        </div>
        <div class="col-sm-4">
            <select class="form-control" ref="currency_select" name="foreign_currency[]" @input="handleInput">
                <option
                        v-for="currency in this.enabledCurrencies"
                        v-if="currency.enabled"
                        :value="currency.id"
                        :label="currency.name"
                        :selected="value.currency_id === currency.id"

                >
                    {{ currency.name }}
                </option>
            </select>
        </div>
        <div class="col-sm-8">
            <input type="number" @input="handleInput" ref="amount" :value="value.amount" step="any" class="form-control"
                   name="foreign_amount[]" v-if="this.enabledCurrencies.length > 0"
                   :title="this.title" autocomplete="off" :placeholder="this.title">

            <ul class="list-unstyled" v-for="error in this.error">
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
            //console.log('loadCurrencies()');
            this.liability = false;
            this.loadCurrencies();
        },
        data() {
            return {
                currencies: [],
                enabledCurrencies: [],
                exclude: null,
                // liability overrules the drop down list if the source or dest is a liability
                liability: false
            }
        },
        watch: {
            source: function () {
                // console.log('watch source in foreign currency');
                this.changeData();
            },
            destination: function () {
                // console.log('watch destination in foreign currency');
                this.changeData();
            },
            transactionType: function () {
                // console.log('watch transaction type in foreign currency');
                this.changeData();
            }
        },
        methods: {
            hasError: function () {
                // console.log('Has error');
                return this.error.length > 0;
            },
            handleInput(e) {
                // console.log('handleInput');
                let obj = {
                    amount: this.$refs.amount.value,
                    currency_id: this.$refs.currency_select.value,
                };
                // console.log(obj);
                this.$emit('input', obj
                );
            },
            changeData: function () {
                //console.log('Now in changeData()');
                this.enabledCurrencies = [];
                let destType = this.destination.type ? this.destination.type.toLowerCase() : 'invalid';
                let srcType = this.source.type ? this.source.type.toLowerCase() : 'invalid';
                let tType =this.transactionType ? this.transactionType.toLowerCase() : 'invalid';
                let liabilities = ['loan','debt','mortgage'];
                let sourceIsLiability = liabilities.indexOf(srcType) !== -1;
                let destIsLiability = liabilities.indexOf(destType) !== -1;

                // console.log(srcType + ' (source) is a liability: ' + sourceIsLiability);
                // console.log(destType + ' (dest) is a liability: ' + destIsLiability);

                if (tType === 'transfer' || destIsLiability || sourceIsLiability) {
                    //console.log('Source is liability OR dest is liability, OR transfer. Lock list on currency of destination.');
                    this.liability = true;
                    // lock dropdown list on on currencyID of destination.
                    for (const key in this.currencies) {
                        if (this.currencies.hasOwnProperty(key) && /^0$|^[1-9]\d*$/.test(key) && key <= 4294967294) {
                            if (this.currencies[key].id === this.destination.currency_id) {
                                this.enabledCurrencies.push(this.currencies[key]);
                            }
                        }
                    }
                    //console.log('Enabled currencies length is now ' + this.enabledCurrencies.length);
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
                let URI = document.getElementsByTagName('base')[0].href + "json/currencies";
                axios.get(URI, {}).then((res) => {
                    this.currencies = [
                        {
                            name: this.no_currency,
                            id: 0,
                            enabled: true
                        }
                    ];
                    for (const key in res.data) {
                        if (res.data.hasOwnProperty(key) && /^0$|^[1-9]\d*$/.test(key) && key <= 4294967294) {
                            if (res.data[key].enabled) {
                                this.currencies.push(res.data[key]);
                                this.enabledCurrencies.push(res.data[key]);
                            }
                        }
                    }
                });
            }
        }
    }
</script>

<style scoped>

</style>