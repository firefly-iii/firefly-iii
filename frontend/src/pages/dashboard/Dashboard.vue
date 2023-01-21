<!--
  - Dashboard.vue
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
  <q-page class="q-ma-md">
    <div class="row">
      <div class="col-xl-4 col-lg-4 col-md-6 q-pr-sm">
        <BillInsightBox/>
      </div>
      <div class="col-xl-4 col-lg-4 col-md-6 q-px-sm">
        <SpendInsightBox/>
      </div>
      <div class="col-xl-4 col-lg-4 col-md-6 q-pl-sm">
        <NetWorthInsightBox/>
      </div>
    </div>
    <div class="row">
      <div class="col-12 q-pt-sm q-pb-sm">
        <AccountChart/>
      </div>
    </div>
    <div class="row">
      <div class="col">
        <TransactionLists/>
      </div>
    </div>
    <div class="row">
      <div class="col">
        <BudgetBox/>
      </div>
      <div class="col">
        Category box
      </div>
    </div>
    <div class="row">
      <div class="col">
        Expense Box
      </div>
      <div class="col">
        Revenue Box
      </div>
    </div>
    <div class="row">
      <div class="col">
        Piggy box
      </div>
      <div class="col">
        Bill box
      </div>
    </div>
    <q-page-sticky :offset="[18, 18]" position="bottom-right">
      <q-fab
        color="green"
        direction="up"
        icon="fas fa-chevron-up"
        label="Actions"
        label-position="left"
        square
        vertical-actions-align="right"
      >
        <q-fab-action :label="$t('firefly.new_budget')" :to="{ name: 'budgets.create' }" color="primary"
                      icon="fas fa-chart-pie"
                      square/>
        <q-fab-action :label="$t('firefly.new_asset_account')"
                      :to="{ name: 'accounts.create', params: {type: 'asset'} }" color="primary"
                      icon="far fa-money-bill-alt"
                      square/>
        <q-fab-action :label="$t('firefly.newTransfer')"
                      :to="{ name: 'transactions.create', params: {type: 'transfer'} }" color="primary"
                      icon="fas fa-exchange-alt"
                      square/>
        <q-fab-action :label="$t('firefly.newDeposit')" :to="{ name: 'transactions.create', params: {type: 'deposit'} }"
                      color="primary" icon="fas fa-long-arrow-alt-right"
                      square/>
        <q-fab-action :label="$t('firefly.newWithdrawal')"
                      :to="{ name: 'transactions.create', params: {type: 'withdrawal'} }" color="primary"
                      icon="fas fa-long-arrow-alt-left"
                      square/>
      </q-fab>
    </q-page-sticky>
  </q-page>
</template>

<script>
import {defineAsyncComponent} from "vue";

export default {
  name: "Dashboard",
  components: {
    TransactionLists: defineAsyncComponent(() => import("../../components/dashboard/TransactionLists.vue")),
    AccountChart: defineAsyncComponent(() => import('../../components/dashboard/AccountChart.vue')),
    NetWorthInsightBox: defineAsyncComponent(() => import('../../components/dashboard/NetWorthInsightBox.vue')),
    BillInsightBox: defineAsyncComponent(() => import('../../components/dashboard/BillInsightBox.vue')),
    SpendInsightBox: defineAsyncComponent(() => import('../../components/dashboard/SpendInsightBox.vue')),
    BudgetBox: defineAsyncComponent(() => import('../../components/dashboard/BudgetBox.vue')),
  }
}
</script>
