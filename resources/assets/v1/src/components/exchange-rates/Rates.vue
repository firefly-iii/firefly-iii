<!--
  - Rates.vue
  - Copyright (c) 2024 james@firefly-iii.org.
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
  - along with this program.  If not, see https://www.gnu.org/licenses/.
  -->


<template>
    <div>
        <div class="row">
            <div class="col-lg-8 col-lg-offset-2 col-md-12 col-sm-12 col-xs-12">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title">{{ $t('firefly.header_exchange_rates_rates') }}</h3>
                    </div>
                    <div class="box-body">
                        <p>
                            {{ $t('firefly.exchange_rates_intro_rates') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-8 col-lg-offset-2 col-md-12 col-sm-12 col-xs-12">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title">{{ $t('firefly.header_exchange_rates_table') }}</h3>
                    </div>
                    <div class="box-body no-padding">
                        <table class="table table-responsive table-hover">
                            <thead>
                            <tr>
                                <th>{{ $t('form.date') }}</th>
                                <th v-html="$t('form.from_currency_to_currency', {from: from.code, to: to.code})"></th>
                                <th v-html="$t('form.to_currency_from_currency', {from: from.code, to: to.code})"></th>
                                <th>&nbsp;</th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr v-if="loading">
                                <td colspan="4" class="text-center">
                                    <i class="fa fa-refresh fa-spin"></i>
                                </td>
                            </tr>
                            <tr v-if="0 === this.rates.length">
                                <td colspan="4" class="text-center">
                                    <i class="fa fa-battery-empty"></i>
                                </td>
                            </tr>
                            <tr v-for="(rate, index) in rates" :key="rate.key">
                                <td>
                                    <input
                                        ref="date"
                                        :value="rate.date_field"
                                        autocomplete="off"
                                        class="form-control"
                                        name="date[]"
                                        type="date"
                                        v-bind:placeholder="$t('firefly.date')"
                                        v-bind:title="$t('firefly.date')"
                                    >
                                    </td>
                                <td>
                                    <input type="number" class="form-control" min="0" v-model="rate.rate">
                                </td>
                                <td>
                                    <input type="number" class="form-control" min="0" v-model="rate.inverse">
                                </td>
                                <td>
                                    <button class="btn btn-danger" @click="deleteRate(index)">
                                        <em class="fa fa-trash"></em>
                                    </button>
                                    update + delete
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<script>

import format from "date-fns/format";

export default {
    name: "Rates",
    data() {
        return {
            rates: [],
            tempRates: {},
            from_code: '',
            to_code: '',
            from: {
                name: ''
            },
            to: {
                name: ''
            },
            loading: true,
        };
    },
    mounted() {
        // get from and to code from URL
        let parts = window.location.href.split('/');
        this.from_code = parts[parts.length - 2].substring(0, 3);
        this.to_code = parts[parts.length - 1].substring(0, 3);
        this.downloadCurrencies();
        this.downloadRates(1);
    },
    methods: {
        deleteRate: function(index) {
            console.log(this.rates[index].key);
            this.rates.splice(index, 1);
        },
        updateRate: function(index) {
            console.log('Update!');
            console.log(this.rates[index].key);
        },
        downloadCurrencies: function () {
            axios.get("./api/v2/currencies/" + this.from_code).then((response) => {
                this.from = {
                    id: response.data.data.id,
                    code: response.data.data.attributes.code,
                    name: response.data.data.attributes.name,
                }
            });
            axios.get("./api/v2/currencies/" + this.to_code).then((response) => {
                console.log(response.data.data);
                this.to = {
                    id: response.data.data.id,
                    code: response.data.data.attributes.code,
                    name: response.data.data.attributes.name,
                }
            });
        },
        downloadRates: function (page) {
            axios.get("./api/v2/exchange-rates/rates/" + this.from_code + '/' + this.to_code + '?page=' + page).then((response) => {
                for (let i in response.data.data) {
                    if (response.data.data.hasOwnProperty(i)) {
                        let current = response.data.data[i];
                        let date = new Date(current.attributes.date);
                        let from_code = current.attributes.from_currency_code;
                        let to_code = current.attributes.to_currency_code;
                        let rate = current.attributes.rate;
                        let inverse = '';
                        let key = from_code + '_' + to_code + '_' + format(date, 'yyyy-MM-dd');
                        console.log('Key is now "' + key + '"');

                        // perhaps the returned rate is actually the inverse rate.
                        if(from_code === this.to_code && to_code === this.from_code) {
                            console.log('Inverse rate found!');
                            key = to_code + '_' + from_code + '_' + format(date, 'yyyy-MM-dd');
                            rate = '';
                            inverse = current.attributes.rate;
                            console.log('Key updated to "' + key + '"');
                        }
                        // inverse is not "" and existing inverse is ""?
                        if (this.tempRates.hasOwnProperty(key) && inverse !== '' && this.tempRates[key].inverse === '') {
                            this.tempRates[key].inverse = inverse;
                        }
                        // rate is not "" and existing rate is ""?
                        if (this.tempRates.hasOwnProperty(key) && rate !== '' && this.tempRates[key].rate === '') {
                            this.tempRates[key].rate = rate;
                        }

                        if (!this.tempRates.hasOwnProperty(key)) {
                            this.tempRates[key] = {
                                key: key,
                                date: date,
                                date_formatted: format(date, this.$t('config.date_time_fns')),
                                date_field: current.attributes.date.substring(0, 10),
                                rate: rate,
                                inverse: '',
                            };
                        }
                    }
                }
                if (parseInt(response.data.meta.pagination.current_page) < parseInt(response.data.meta.pagination.total_pages)) {
                    this.downloadRates(page + 1);
                }
                if (parseInt(response.data.meta.pagination.current_page) === parseInt(response.data.meta.pagination.total_pages)) {
                    this.loading = false;
                    this.rates = Object.values(this.tempRates);
                }
            });
        }
    },

}

</script>
