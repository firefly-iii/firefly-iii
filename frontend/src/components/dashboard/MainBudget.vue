<!--
  - MainBudgetChart.vue
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
            <h3 class="card-title">{{ $t('firefly.budgets') }}</h3>
        </div>
        <div class="card-body">
            <div>
                <main-budget-chart v-if="loaded" :styles="chartStyles" :options="chartOptions" :chart-data="chartData"></main-budget-chart>
            </div>
        </div>
        <div class="card-footer">
            <a href="./budgets" class="btn btn-default button-sm"><i class="far fa-money-bill-alt"></i> {{ $t('firefly.go_to_budgets') }}</a>
        </div>
    </div>
</template>

<script>
    import MainBudgetChart from "./MainBudgetChart";
    import DefaultBarOptions from "../charts/DefaultBarOptions";
    import DataConverter from "../charts/DataConverter";

    export default {
        name: "MainBudget",
        components: {
            MainBudgetChart
        },
        data() {
            return {
                chartData: null,
                loaded: false,
                chartOptions: null,
            }
        },
        mounted() {
            this.chartOptions = DefaultBarOptions.methods.getDefaultOptions();
            this.loaded = false;
            axios.get('./api/v1/chart/budget/overview?start=' + window.sessionStart + '&end=' + window.sessionEnd)
                .then(response => {
                    this.chartData = response.data;
                    //this.chartData = DataConverter.methods.colorizeData(this.chartData);
                    this.chartData = DataConverter.methods.convertChart(this.chartData);
                    this.loaded = true
                });
        },
        methods: {
        },
        computed: {
            chartStyles() {
                return {
                    height: '400px',
                    'max-height': '400px',
                    position: 'relative',
                    display: 'block',
                }
            }
        },
    }
</script>

<style scoped>

</style>
