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
  <!-- TODO main DIV always use q-ma-md for the main holder-->
  <!-- TODO rows use a q-mb-sm to give them space -->
  <div class="q-ma-md">
    <div class="row q-mb-sm">
      <div class="col">
        <BillInsightBox />
      </div>
      <div class="col">
        <SpendInsightBox />
      </div>
      <div class="col">
        <NetWorthInsightBox />
      </div>
    </div>
    <div class="row q-mb-sm">
      <div class="col">
        <AccountChart />
      </div>
    </div>
    <div class="row q-mb-sm">
      <div class="col">
        <TransactionLists />
      </div>
    </div>
    <div class="row q-mb-sm">
      <div class="col">
        Budget box
      </div>
      <div class="col">
        Category box
      </div>
    </div>
    <div class="row q-mb-sm">
      <div class="col">
        Expense Box
      </div>
      <div class="col">
        Revenue Box
      </div>
    </div>
    <div class="row q-mb-sm">
      <div class="col">
        Piggy box
      </div>
      <div class="col">
        Bill box
      </div>
    </div>
    <q-page-sticky position="bottom-right" :offset="[18, 18]">
      <q-fab
        label="Actions"
        square
        vertical-actions-align="right"
        label-position="left"
        color="green"
        icon="fas fa-chevron-up"
        direction="up"
      >
        <q-fab-action color="primary" square icon="fas fa-chart-pie" :label="$t('firefly.new_budget')" :to="{ name: 'budgets.create' }"/>
        <q-fab-action color="primary" square icon="far fa-money-bill-alt" :label="$t('firefly.new_asset_account')" :to="{ name: 'accounts.create', params: {type: 'asset'} }"/>
        <q-fab-action color="primary" square icon="fas fa-exchange-alt" :label="$t('firefly.newTransfer')" :to="{ name: 'transactions.create', params: {type: 'transfer'} }"/>
        <q-fab-action color="primary" square icon="fas fa-long-arrow-alt-right" :label="$t('firefly.newDeposit')" :to="{ name: 'transactions.create', params: {type: 'deposit'} }"/>
        <q-fab-action color="primary" square icon="fas fa-long-arrow-alt-left" :label="$t('firefly.newWithdrawal')" :to="{ name: 'transactions.create', params: {type: 'withdrawal'} }"/>
      </q-fab>
    </q-page-sticky>
  </div>
</template>

<script>
import {defineAsyncComponent} from "vue";
import TransactionLists from "../../components/dashboard/TransactionLists";

export default {
  name: "Dashboard",
  components: {
    TransactionLists,
    AccountChart: defineAsyncComponent(() => import('../../components/dashboard/AccountChart.vue')),
    NetWorthInsightBox: defineAsyncComponent(() => import('../../components/dashboard/NetWorthInsightBox.vue')),
    BillInsightBox: defineAsyncComponent(() => import('../../components/dashboard/BillInsightBox.vue')),
    SpendInsightBox: defineAsyncComponent(() => import('../../components/dashboard/SpendInsightBox.vue')),
  }
}
</script>
