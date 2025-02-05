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
                        <nav v-if="totalPages > 1">
                            <ul class="pagination">
                                <li v-if="1 === this.page" class="page-item disabled" aria-disabled="true" :aria-label="$t('pagination.previous')">
                                    <span class="page-link" aria-hidden="true">&lsaquo;</span>
                                </li>
                                <li class="page-item" v-if="1 !== this.page">
                                    <a class="page-link" :href="'/exchange-rates/'+from_code+'/'+to_code+'?page=' + (this.page-1)" rel="prev" :aria-label="$t('pagination.next')">&lsaquo;</a>
                                </li>
                                <li v-for="item in this.totalPages" :class="item === page ? 'page-item active' : 'page-item'" aria-current="page">
                                    <span v-if="item === page" class="page-link" v-text="item"></span>
                                    <a v-if="item !== page" class="page-link" :href="'/exchange-rates/'+from_code+'/'+to_code+'?page=' + item" v-text="item"></a>
                                </li>
                                <li v-if="totalPages !== page" class="page-item">
                                    <a class="page-link" :href="'/exchange-rates/'+from_code+'/'+to_code+'?page=' + (this.page+1)" rel="next" :aria-label="$t('pagination.next')">&rsaquo;</a>
                                </li>
                                <li v-if="totalPages === page" class="page-item disabled" aria-disabled="true" :aria-label="$t('pagination.next')">
                                    <span class="page-link" aria-hidden="true">&rsaquo;</span>
                                </li>
                            </ul>
                        </nav>

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
                                    <input type="number" class="form-control" min="0" step="any" v-model="rate.rate">
                                </td>
                                <td>
                                    <input type="number" class="form-control" min="0" step="any" v-model="rate.inverse">
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <button
                                            :disabled="saveButtonDisabled(index)"
                                            class="btn btn-default" :title="$t('firefly.submit')"
                                            @click="updateRate(index)">
                                            <em class="fa fa-save"></em>
                                        </button>
                                        <button class="btn btn-danger" :title="$t('firefly.delete')"
                                                @click="deleteRate(index)">
                                            <em class="fa fa-trash"></em>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            </tbody>
                        </table>

                        <nav v-if="totalPages > 1">
                            <ul class="pagination">
                                <li v-if="1 === this.page" class="page-item disabled" aria-disabled="true" :aria-label="$t('pagination.previous')">
                                    <span class="page-link" aria-hidden="true">&lsaquo;</span>
                                </li>
                                <li class="page-item" v-if="1 !== this.page">
                                    <a class="page-link" :href="'/exchange-rates/'+from_code+'/'+to_code+'?page=' + (this.page-1)" rel="prev" :aria-label="$t('pagination.next')">&lsaquo;</a>
                                </li>
                                <li v-for="item in this.totalPages" :class="item === page ? 'page-item active' : 'page-item'" aria-current="page">
                                    <span v-if="item === page" class="page-link" v-text="item"></span>
                                    <a v-if="item !== page" class="page-link" :href="'/exchange-rates/'+from_code+'/'+to_code+'?page=' + item" v-text="item"></a>
                                </li>
                                <li v-if="totalPages !== page" class="page-item">
                                    <a class="page-link" :href="'/exchange-rates/'+from_code+'/'+to_code+'?page=' + (this.page+1)" rel="next" :aria-label="$t('pagination.next')">&rsaquo;</a>
                                </li>
                                <li v-if="totalPages === page" class="page-item disabled" aria-disabled="true" :aria-label="$t('pagination.next')">
                                    <span class="page-link" aria-hidden="true">&rsaquo;</span>
                                </li>
                            </ul>
                        </nav>

                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-8 col-lg-offset-2 col-md-12 col-sm-12 col-xs-12">
                <form class="form-horizontal nodisablebutton" @submit="submitRate">
                    <div class="box box-default">
                        <div class="box-header with-border">
                            <h3 class="box-title">{{ $t('firefly.add_new_rate') }}</h3>
                        </div>
                        <div class="box-body">
                            <p v-if="newError !=''" v-text="newError" class="text-danger">

                            </p>
                            <div class="form-group" id="name_holder">
                                <label for="ffInput_date" class="col-sm-4 control-label"
                                       v-text="$t('form.date')"></label>
                                <div class="col-sm-8">
                                    <input class="form-control" type="date" name="date" id="ffInput_date" :disabled="posting"
                                           autocomplete="off" spellcheck="false" v-model="newDate">
                                </div>
                            </div>
                            <div class="form-group" id="rate_holder">
                                <label for="ffInput_rate" class="col-sm-4 control-label"
                                       v-text="$t('form.rate')"></label>
                                <div class="col-sm-8">
                                    <input class="form-control" type="number" name="rate" id="ffInput_rate" :disabled="posting"
                                           autocomplete="off" spellcheck="false" v-model="newRate" step="any">
                                    <p class="help-block" v-text="$t('firefly.help_rate_form', {from: from_code, to: to_code})">

                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="box-footer">
                            <button type="submit" class="nodisablebutton btn pull-right btn-success" v-text="$t('firefly.save_new_rate')"></button>
                        </div>
                    </div>

                </form>
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
            newDate: '',
            newRate: '1.0',
            newError: '',
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
            posting: false,
            updating: false,
            page: 1,
            totalPages: 1,
        };
    },
    mounted() {
        // get from and to code from URL
        this.newDate = format(new Date, 'yyyy-MM-dd');
        let parts = window.location.href.split('/');
        this.from_code = parts[parts.length - 2].substring(0, 3);
        this.to_code = parts[parts.length - 1].substring(0, 3);

        const params = new Proxy(new URLSearchParams(window.location.search), {
            get: (searchParams, prop) => searchParams.get(prop),
        });
        this.page = parseInt(params.page ?? 1);


        this.downloadCurrencies();
        this.rates = [];
        this.downloadRates(this.page);
    },
    methods: {
        submitRate: function(e) {
            if(e) e.preventDefault();
            this.posting = true;

            axios.post("./api/v1/exchange-rates", {
                from: this.from_code,
                to: this.to_code,
                rate: this.newRate,
                date: this.newDate,
            }).then(() => {
                this.posting = false;
                this.downloadRates(1);
            }).catch((err) => {
                this.posting = false;
                this.newError = err.response.data.message;
            });


            return false;
        },
        saveButtonDisabled: function (index) {
            return ('' === this.rates[index].rate && '' === this.rates[index].inverse) || this.updating;
        },
        updateRate: function (index) {
            let parts = this.spliceKey(this.rates[index].key);
            if (0 === parts.length) {
                return;
            }
            if ('' !== this.rates[index].rate) {
                this.updating = true;
                axios.put("./api/v1/exchange-rates/" + this.rates[index].rate_id, {rate: this.rates[index].rate})
                    .then(() => {
                        this.updating = false;
                    });
            }
            if ('' !== this.rates[index].inverse) {
                this.updating = true;
                axios.put("./api/v1/exchange-rates/" + this.rates[index].inverse_id, {rate: this.rates[index].inverse})
                    .then(() => {
                        this.updating = false;
                    });
            }
        },
        deleteRate: function (index) {
            // console.log(this.rates[index].key);
            let parts = this.spliceKey(this.rates[index].key);
            if (0 === parts.length) {
                return;
            }
            // console.log(parts);

            // delete A to B
            axios.delete("./api/v1/exchange-rates/rates/" + parts.from + '/' + parts.to + '?date=' + format(parts.date, 'yyyy-MM-dd'));
            // delete B to A.
            axios.delete("./api/v1/exchange-rates/rates/" + parts.to + '/' + parts.from + '?date=' + format(parts.date, 'yyyy-MM-dd'));

            this.rates.splice(index, 1);
        },

        spliceKey: function (key) {
            if (key.length !== 18) {
                return [];
            }
            let main = key.split('_');
            if (3 !== main.length) {
                return [];
            }
            let date = new Date(main[2]);
            return {
                from: main[0],
                to: main[1],
                date: date,
            };
        },
        downloadCurrencies: function () {
            this.loading = true;
            axios.get("./api/v1/currencies/" + this.from_code).then((response) => {
                this.from = {
                    id: response.data.data.id,
                    code: response.data.data.attributes.code,
                    name: response.data.data.attributes.name,
                }
            });
            axios.get("./api/v1/currencies/" + this.to_code).then((response) => {
                // console.log(response.data.data);
                this.to = {
                    id: response.data.data.id,
                    code: response.data.data.attributes.code,
                    name: response.data.data.attributes.name,
                }
            });
        },
        downloadRates: function (page) {
            this.tempRates = {};
            this.loading = true;
            axios.get("./api/v1/exchange-rates/rates/" + this.from_code + '/' + this.to_code + '?page=' + page).then((response) => {
                for (let i in response.data.data) {
                    if (response.data.data.hasOwnProperty(i)) {
                        let current = response.data.data[i];
                        let date = new Date(current.attributes.date);
                        let from_code = current.attributes.from_currency_code;
                        let to_code = current.attributes.to_currency_code;
                        let rate = current.attributes.rate;
                        let inverse = '';
                        let rate_id = current.id;
                        let inverse_id = '0';
                        let key = from_code + '_' + to_code + '_' + format(date, 'yyyy-MM-dd');
                        // console.log('Key is now "' + key + '"');

                        // perhaps the returned rate is actually the inverse rate.
                        if (from_code === this.to_code && to_code === this.from_code) {
                            // console.log('Inverse rate found!');
                            key = to_code + '_' + from_code + '_' + format(date, 'yyyy-MM-dd');
                            rate = '';
                            inverse = current.attributes.rate;
                            inverse_id = current.id;
                            // console.log('Key updated to "' + key + '"');
                        }

                        if (!this.tempRates.hasOwnProperty(key)) {
                            this.tempRates[key] = {
                                key: key,
                                date: date,
                                rate_id: rate_id,
                                inverse_id: inverse_id,
                                date_formatted: format(date, this.$t('config.date_time_fns')),
                                date_field: current.attributes.date.substring(0, 10),
                                rate: rate,
                                inverse: '',
                            };
                        }

                        // inverse is not "" and existing inverse is ""?
                        if (this.tempRates.hasOwnProperty(key) && inverse !== '' && this.tempRates[key].inverse === '') {
                            this.tempRates[key].inverse = inverse;
                            this.tempRates[key].inverse_id = inverse_id;
                        }
                        // rate is not "" and existing rate is ""?
                        if (this.tempRates.hasOwnProperty(key) && rate !== '' && this.tempRates[key].rate === '') {
                            this.tempRates[key].rate = rate;
                            this.tempRates[key].rate_id = rate_id;
                        }


                    }
                }
                this.totalPages = parseInt(response.data.meta.pagination.total_pages);
                this.loading = false;
                this.rates = Object.values(this.tempRates);
                console.log('Do not download more pages. Now on page ' + this.page + ' of ' + this.totalPages);
            });
        }
    },

}

</script>
