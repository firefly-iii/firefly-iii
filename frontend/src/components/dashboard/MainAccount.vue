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
            this.chartOptions = {
                responsive: true,
                maintainAspectRatio: false
            };

            this.loaded = false;
            axios.get('./api/v1/chart/account/overview?start=' + window.sessionStart + '&end=' + window.sessionEnd)
                .then(response => {
                    this.chartData = DataConverter.methods.convertChart(response.data);
                    console.log(this.chartData);
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
