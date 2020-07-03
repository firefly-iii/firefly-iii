<!--
  - TopBoxes.vue
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
    <div class="row">
        <div class="col-md-3 col-sm-6 col-12">
            <div class="info-box">
                <span class="info-box-icon"><i class="far fa-bookmark text-info"></i></span>

                <div class="info-box-content">
                    <span class="info-box-text">{{ $t("firefly.balance") }}</span>
                    <!-- dont take the first, take default currency OR first -->
                    <span class="info-box-number" v-if="balances.length > 0">{{ balances[0].value_parsed }}</span>

                    <div class="progress bg-info">
                        <div class="progress-bar" style="width: 0"></div>
                    </div>
                    <span class="progress-description">
                        <span v-for="balance in balances">{{ balance.sub_title }}<br></span>
                    </span>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-md-3">
            <div class="info-box">
                <span class="info-box-icon"><i class="far fa-calendar-alt text-teal"></i></span>

                <div class="info-box-content">
                    <span class="info-box-text"><span>{{ $t('firefly.bills_to_pay') }}</span></span>
                    <!-- dont take the first, take default currency OR first -->
                    <span class="info-box-number" v-if="1 === billsUnpaid.length && billsPaid.length > 0">{{ billsUnpaid[0].value_parsed }}</span>

                    <div class="progress bg-teal">
                        <div class="progress-bar" style="width: 0"></div>
                    </div>
                    <span class="progress-description">
                        <!-- dont take the first, take default currency OR first -->
                        <span v-if="1 === billsUnpaid.length && 1 === billsPaid.length">{{ $t('firefly.paid') }}: {{ billsPaid[0].value_parsed }}</span>
                        <span v-if="billsUnpaid.length > 1">
                            <span v-for="(bill, index) in billsUnpaid" :key="bill.key">
                                {{ bill.value_parsed }}<span v-if="index+1 !== billsUnpaid.length">, </span>
                            </span>
                        </span>
                    </span>
                </div>
            </div>
        </div>

        <!-- altijd iets in bold -->
        <!-- subtitle verschilt -->
        <!-- fix for small devices only -->
        <div class="clearfix hidden-md-up"></div>

        <!-- left to spend -->
        <div class="col-12 col-sm-6 col-md-3">
            <div class="info-box">
                <span class="info-box-icon"><i class="fas fa-money-bill text-success"></i></span>

                <div class="info-box-content">
                    <span class="info-box-text"><span>{{ $t('firefly.left_to_spend') }}</span></span>
                    <!-- dont take the first, take default currency OR first -->
                    <!-- change color if negative -->
                    <span class="info-box-number" v-if="leftToSpend.length > 0">{{ leftToSpend[0].value_parsed }}</span>

                    <div class="progress bg-success">
                        <div class="progress-bar" style="width: 0"></div>
                    </div>
                    <span class="progress-description">
                        <!-- list all EXCEPT default currency -->
                           <span v-for="(spent, index) in leftToSpend" :key="spent.key">
                                {{ spent.value_parsed }}<span v-if="index+1 !== leftToSpend.length">, </span>
                            </span>
                    </span>
                </div>
            </div>
        </div>

        <!-- net worth -->
        <div class="col-12 col-sm-6 col-md-3">
            <div class="info-box">
                <span class="info-box-icon"><i class="fas fa-money-bill text-success"></i></span>

                <div class="info-box-content">
                    <span class="info-box-text"><span>{{ $t('firefly.net_worth') }}</span></span>
                    <!-- dont take the first, take default currency OR first -->
                    <span class="info-box-number" v-if="netWorth.length > 0">{{ netWorth[0].value_parsed }}</span>

                    <div class="progress bg-success">
                        <div class="progress-bar" style="width: 0"></div>
                    </div>
                    <span class="progress-description">
                        <!-- list all EXCEPT default currency -->
                           <span v-for="(net, index) in netWorth" :key="net.key">
                                {{ net.value_parsed }}<span v-if="index+1 !== net.length">, </span>
                            </span>
                    </span>
                </div>
            </div>
        </div>

    </div>
</template>

<script>
    export default {
        name: "TopBoxes",
        data() {
            return {
                summary: [],
                balances: [],
                billsPaid: [],
                billsUnpaid: [],
                leftToSpend: [],
                netWorth: [],
            }
        },
        mounted() {
            this.prepareComponent();
        },
        methods: {
            /**
             * Prepare the component.
             */
            prepareComponent() {
                axios.get('./api/v1/summary/basic?start=' + window.sessionStart + '&end=' + window.sessionEnd)
                    .then(response => {
                        this.summary = response.data;
                        this.buildComponent();
                    });
            },
            buildComponent() {
                this.getBalanceEntries();
                this.getBillsEntries();
                this.getLeftToSpend();
                this.getNetWorth();
            },
            getBalanceEntries() {
                this.balances = this.getKeyedEntries('balance-in-');
            },
            getNetWorth() {
                this.netWorth = this.getKeyedEntries('net-worth-in-');
            },
            getLeftToSpend() {
                this.leftToSpend = this.getKeyedEntries('left-to-spend-in-');
            },
            getBillsEntries() {
                this.billsPaid = this.getKeyedEntries('bills-paid-in-');
                this.billsUnpaid = this.getKeyedEntries('bills-unpaid-in-');
            },
            getKeyedEntries(expected) {
                let result = [];
                for (const key in this.summary) {
                    if (this.summary.hasOwnProperty(key)) {
                        if (expected === key.substr(0, expected.length)) {
                            result.push(this.summary[key]);
                        }
                    }
                }
                return result;
            }
        }
    }
</script>

<style scoped>

</style>
