<!--
  - ForeignAmountSelect.vue
  - Copyright (c) 2019 thegrumpydictator@gmail.com
  -
  - This file is part of Firefly III.
  -
  - Firefly III is free software: you can redistribute it and/or modify
  - it under the terms of the GNU General Public License as published by
  - the Free Software Foundation, either version 3 of the License, or
  - (at your option) any later version.
  -
  - Firefly III is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU General Public License for more details.
  -
  - You should have received a copy of the GNU General Public License
  - along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
  -->

<template>
    <div class="form-group" v-bind:class="{ 'has-error': hasError()}" v-if="(this.enabledCurrencies.length > 2 && this.transactionType === 'Deposit') || this.transactionType.toLowerCase() === 'transfer'">
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
                   title="Foreign amount" autocomplete="off" placeholder="Foreign amount">

            <ul class="list-unstyled" v-for="error in this.error">
                <li class="text-danger">{{ error }}</li>
            </ul>
        </div>
    </div>
</template>

<script>
    export default {
        name: "ForeignAmountSelect",
        props: ['source', 'destination', 'transactionType', 'value','error'],
        mounted() {
            this.loadCurrencies();
        },
        data() {
            return {
                currencies: [],
                enabledCurrencies: [],
                exclude: null
            }
        },
        watch: {
            source: function () {
                this.changeData();
            },
            destination: function () {
                this.changeData();
            },
            transactionType: function () {
                this.changeData();
            }
        },
        methods: {
            hasError: function () {
                return this.error.length > 0;
            },
            handleInput(e) {
                this.$emit('input', {
                    amount: +this.$refs.amount.value,
                    currency_id: this.$refs.currency_select.value,
                }
                );
            },
            changeData: function () {
                this.enabledCurrencies = [];
                if (this.transactionType === 'Transfer') {
                    // lock source on currencyID of destination
                    for (const key in this.currencies) {
                        if (this.currencies.hasOwnProperty(key) && /^0$|^[1-9]\d*$/.test(key) && key <= 4294967294) {
                            if (this.currencies[key].id === this.destination.currency_id) {
                                this.enabledCurrencies.push(this.currencies[key]);
                            }
                        }
                    }
                    console.log('Enabled currencies length is now ' + this.enabledCurrencies.length);
                    return;
                }
                // if type is withdrawal, list all but skip the source account ID.
                if (this.transactionType === 'Withdrawal' && this.source) {
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
                if (this.transactionType === 'Deposit' && this.destination) {
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
                let URI = document.getElementsByTagName('base')[0].href + "json/currencies";
                axios.get(URI, {}).then((res) => {
                    this.currencies = [
                        {
                            name: '(none)',
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