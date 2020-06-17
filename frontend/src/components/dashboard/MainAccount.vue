<template>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">{{ $t('firefly.yourAccounts') }}</h3>
        </div>
        <div class="card-body">
            <div class="main-account-chart">
                <main-account-chart :styles="myStyles" :options="datacollection.options" :chart-data="datacollection"></main-account-chart>
            </div>
        </div>
        <div class="card-footer">
            <a href="./accounts/asset" class="btn btn-default button-sm"><i class="far fa-money-bill-alt"></i> {{ $t('firefly.go_to_asset_accounts') }}</a>
        </div>
    </div>
</template>

<script>
    import MainAccountChart from "./MainAccountChart";

    export default {
        components: {
            MainAccountChart
        },
        data() {
            return {
                datacollection: null
            }
        },
        mounted() {
            this.fillData()
        },
        methods: {
            fillData() {
                this.datacollection = {
                    options: {
                        responsive: true,
                        maintainAspectRatio: false
                    },
                    labels: [this.getRandomInt(), this.getRandomInt()],
                    datasets: [
                        {
                            label: 'Data One',
                            backgroundColor: '#f87979',
                            data: [this.getRandomInt(), this.getRandomInt()]
                        }, {
                            label: 'Data One',
                            backgroundColor: '#f87979',
                            data: [this.getRandomInt(), this.getRandomInt()]
                        }
                    ]
                }
            },
            getRandomInt() {
                return Math.floor(Math.random() * (50 - 5 + 1)) + 5
            }
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
