<!--
  - Index.vue
  - Copyright (c) 2022 james@firefly-iii.org
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
    <div>
        <div class="row">
            <div class="col-lg-8 col-lg-offset-2 col-md-12 col-sm-12 col-xs-12">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title">{{ $t('firefly.header_exchange_rates') }}</h3>
                    </div>
                    <div class="box-body">
                        <p v-html="$t('firefly.exchange_rates_intro')"></p>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-8 col-lg-offset-2 col-md-12 col-sm-12 col-xs-12" v-if="currencies.length < 2">
                <div class="box box-default" v-for="currency in currencies" :key="currency.id">
                    <div class="box-header with-border">
                        <h3 class="box-title">{{ $t('firefly.not_enough_currencies') }}</h3>
                    </div>
                    <div class="box-body">
                        <p>
                            {{ $t('firefly.not_enough_currencies_enabled') }}
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-lg-8 col-lg-offset-2 col-md-12 col-sm-12 col-xs-12" v-if="currencies.length > 1">
                <div class="box box-default" v-for="currency in currencies" :key="currency.id">
                    <div class="box-header with-border">
                        <h3 class="box-title">{{ currency.name }}</h3>
                    </div>
                    <div class="box-body">
                        <ul v-if="currencies.length > 1">
                            <li v-for="sub in currencies" :key="sub.id" v-show="sub.id !== currency.id">
                                <a :href="'exchange-rates/' + currency.code + '/' + sub.code"
                                   :title="$t('firefly.exchange_rates_from_to', {from: currency.name, to: sub.name})">{{
                                        $t('firefly.exchange_rates_from_to', {
                                            from: currency.name,
                                            to: sub.name
                                        })
                                    }}</a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: "Index",
    data() {
        return {
            currencies: [],
            page: 1,
        };
    },
    mounted() {
        this.getCurrencies();
    },
    methods: {
        getCurrencies: function () {
            this.currencies = [];
            // start with page one, loop for the rest.
            this.downloadCurrencies(1);
        },
        downloadCurrencies: function (page) {
            axios.get("./api/v1/currencies?enabled=1&page=" + page).then((response) => {
                for (let i in response.data.data) {
                    if (response.data.data.hasOwnProperty(i)) {
                        let current = response.data.data[i];
                        if (current.attributes.enabled) {
                            let currency = {
                                id: current.id,
                                name: current.attributes.name,
                                code: current.attributes.code,
                            };
                            this.currencies.push(currency);
                        }
                    }
                }

                if (response.data.meta.pagination.current_page < response.data.meta.pagination.total_pages) {
                    this.downloadCurrencies(parseInt(response.data.meta.pagination.current_page) + 1);
                }
            });
        },
    }
}
</script>

<style scoped>

</style>
