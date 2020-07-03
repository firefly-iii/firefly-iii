<!--
  - MainAccount.vue
  - Copyright (c) 2020 james@firefly-iii.org
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
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">{{ $t('firefly.yourAccounts') }}</h3>
        </div>
        <div class="card-body">
            <div class="main-account-chart">
                <main-account-chart v-if="loaded" :styles="myStyles" :options="chartOptions" :chart-data="chartData"></main-account-chart>
            </div>
        </div>
        <div class="card-footer">
            <a href="./accounts/asset" class="btn btn-default button-sm"><i class="far fa-money-bill-alt"></i> {{ $t('firefly.go_to_asset_accounts') }}</a>
        </div>
    </div>
</template>

<script>
    import MainAccountChart from "./MainAccountChart";
    import DataConverter from "../charts/DataConverter";
    import DefaultLineOptions from "../charts/DefaultLineOptions";

    export default {
        components: {
            MainAccountChart
        },
        data() {
            return {
                chartData: null,
                loaded: false,
                chartOptions: null,
            }
        },
        mounted() {
            this.chartOptions = DefaultLineOptions.methods.getDefaultOptions();


            this.loaded = false;
            axios.get('./api/v1/chart/account/overview?start=' + window.sessionStart + '&end=' + window.sessionEnd)
                .then(response => {
                    this.chartData = DataConverter.methods.convertChart(response.data);
                    this.chartData = DataConverter.methods.colorizeData(this.chartData);
                    this.chartData = DataConverter.methods.convertLabelsToDate(this.chartData);
                    this.loaded = true
                });
        },
        methods: {

        },
        computed: {
            myStyles() {
                return {
                    height: '400px',
                    'max-height': '400px',
                    position: 'relative',
                    display: 'block',
                }
            }
        },
        name: "MainAccount"
    }
</script>
<style scoped>
    .main-account-chart {
    }

</style>
