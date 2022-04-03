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
  <q-page>
    <div class="q-ma-md" v-if="0 === assetCount">
      <NewUser
      v-on:created-accounts="refreshThenCount"
      ></NewUser>
    </div>
    <div class="q-ma-md" v-if="assetCount > 0">
      <Boxes></Boxes>
    </div>
    <div class="row q-ma-md" v-if="assetCount > 0">
      <div class="col-12">
        <q-card bordered>
          <q-card-section>
            <div class="text-h6">Firefly III</div>
            <div class="text-subtitle2">What's playing?</div>
          </q-card-section>
          <q-card-section>
            <HomeChart></HomeChart>
          </q-card-section>
        </q-card>

      </div>
    </div>
    <!--
    <div class="row q-ma-md">
      <div class="col-6 q-pr-sm">
        <q-card bordered>
          <q-card-section>
            <div class="text-h6">Budgets</div>
            <div class="text-subtitle2">Subheader</div>
          </q-card-section>
          <q-card-section>
            Content
          </q-card-section>
        </q-card>


      </div>
      <div class="col-6 q-pl-sm">
        <q-card bordered>
          <q-card-section>
            <div class="text-h6">Categories</div>
            <div class="text-subtitle2">Subheader</div>
          </q-card-section>
          <q-card-section>
            Content
          </q-card-section>
        </q-card>
      </div>
    </div>
    <div class="row">
      <div class="col-6">Expenses</div>
      <div class="col-6">Income</div>
    </div>
    <div class="row">
      <div class="col-4">Account X</div>
      <div class="col-4">Account X</div>
      <div class="col-4">Account X</div>
    </div>
    <div class="row">
      <div class="col-6">Piggies</div>
      <div class="col-6">Bills</div>
    </div>
    -->
    <q-page-sticky position="bottom-right" :offset="[18, 18]" v-if="assetCount > 0">
      <q-fab
        label="Actions"
        square
        vertical-actions-align="right"
        label-position="left"
        color="green"
        icon="fas fa-chevron-up"
        direction="up"
      >
        <!-- <q-fab-action color="primary" square icon="fas fa-bullseye" label="New piggy bank"/> -->
        <q-fab-action color="primary" square icon="fas fa-chart-pie" label="New budget"
                      :to="{ name: 'budgets.create' }"/>
        <!-- <q-fab-action color="primary" square icon="fas fa-home" label="New liability"/> -->
        <q-fab-action color="primary" square icon="far fa-money-bill-alt" label="New asset account"
                      :to="{ name: 'accounts.create', params: {type: 'asset'} }"/>
        <q-fab-action color="primary" square icon="fas fa-exchange-alt" label="New transfer"
                      :to="{ name: 'transactions.create', params: {type: 'transfer'} }"/>
        <q-fab-action color="primary" square icon="fas fa-long-arrow-alt-right" label="New deposit"
                      :to="{ name: 'transactions.create', params: {type: 'deposit'} }"/>
        <q-fab-action color="primary" square icon="fas fa-long-arrow-alt-left" label="New withdrawal"
                      :to="{ name: 'transactions.create', params: {type: 'withdrawal'} }"/>

      </q-fab>
    </q-page-sticky>
  </q-page>
</template>

<script>
import {defineAsyncComponent, defineComponent} from "vue";
import List from "../api/accounts/list";
import {mapGetters} from "vuex";

export default defineComponent(
  {
    name: 'PageIndex',
    components: {
      Boxes: defineAsyncComponent(() => import('./dashboard/Boxes.vue')),
      HomeChart: defineAsyncComponent(() => import('./dashboard/HomeChart')),
      NewUser: defineAsyncComponent(() => import('../components/dashboard/NewUser')),
    },
    data() {
      return {
        assetCount: 1
      }
    },
    computed: {
      ...mapGetters('fireflyiii', ['getCacheKey']),
    },
    mounted() {
      this.countAssetAccounts();
    },
    methods: {
      refreshThenCount: function() {
        this.$store.dispatch('fireflyiii/refreshCacheKey');
        this.countAssetAccounts();
      },
      countAssetAccounts: function () {
        let list = new List;
        list.list('asset',1, this.getCacheKey).then((response) => {
          this.assetCount = parseInt(response.data.meta.pagination.total);
        });
      }
    }
  })
</script>
