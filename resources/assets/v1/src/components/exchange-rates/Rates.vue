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
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
export default {
    name: "Rates",
    data() {
        return {
            rates: [],
            from_code: '',
            to_code: '',
            from: {
                name: ''
            },
            to: {
                name: ''
            },
        };
    },
    mounted() {
        // get from and to code from URL
        let parts = window.location.href.split('/');
        this.from_code = parts[parts.length - 2].substring(0, 3);
        this.to_code = parts[parts.length - 1].substring(0, 3);
        console.log('From: ' + this.from_code + ' To: ' + this.to_code);
        this.downloadCurrencies();
    },
    methods: {
        downloadCurrencies: function() {
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
    }
}

</script>
