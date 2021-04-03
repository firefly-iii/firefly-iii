<!--
  - MainAccountList.vue
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
  <div>
    <!-- row if loading -->
    <div v-if="loading && !error" class="row">
      <div class="col">
        <div class="card">
          <div class="card-body">
            <div class="text-center">
              <i class="fas fa-spinner fa-spin"></i>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- row if error -->
    <div v-if="error" class="row">
      <div class="col">
        <div class="card">
          <div class="card-body">
            <div class="text-center">
              <i class="fas fa-exclamation-triangle text-danger"></i>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- row if normal -->
    <div v-if="!loading && !error" class="row">
      <div
          v-for="account in accounts"
          v-bind:class="{ 'col-lg-12': 1 === accounts.length, 'col-lg-6': 2 === accounts.length, 'col-lg-4': accounts.length > 2 }">
        <div class="card">
          <div class="card-header">
            <h3 class="card-title"><a :href="account.url">{{ account.title }}</a></h3>
            <div class="card-tools">
            <span :class="parseFloat(account.current_balance) < 0 ? 'text-danger' : 'text-success'">
            {{ Intl.NumberFormat(locale, {style: 'currency', currency: account.currency_code}).format(parseFloat(account.current_balance)) }}
              </span>
            </div>
          </div>
          <div class="card-body table-responsive p-0">
            <div>
              <transaction-list-large v-if="1===accounts.length" :account_id="account.id" :transactions="account.transactions"/>
              <transaction-list-medium v-if="2===accounts.length" :account_id="account.id" :transactions="account.transactions"/>
              <transaction-list-small v-if="accounts.length > 2" :account_id="account.id" :transactions="account.transactions"/>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import {createNamespacedHelpers} from "vuex";

const {mapState, mapGetters, mapActions, mapMutations} = createNamespacedHelpers('dashboard/index')

export default {
  name: "MainAccountList",
  data() {
    return {
      loading: true,
      error: false,
      ready: false,
      accounts: [],
      locale: 'en-US'
    }
  },
  created() {
    this.locale = localStorage.locale ?? 'en-US';
    this.ready = true;
  },
  computed: {
    ...mapGetters([
                    'start',
                    'end'
                  ]),
    'datesReady': function () {
      return null !== this.start && null !== this.end && this.ready;
    },
  },
  watch: {
    datesReady: function (value) {
      if (true === value) {
        this.initialiseList();
      }
    },
    start: function () {
      if (false === this.loading) {
        this.initialiseList();
      }
    },
    end: function () {
      if (false === this.loading) {
        this.initialiseList();
      }
    },
  },
  methods: {
    initialiseList: function () {
      this.loading = true;
      this.accounts = [];
      axios.get('./api/v1/preferences/frontpageAccounts')
          .then(response => {
                  this.loadAccounts(response);
                }
          );
    },
    loadAccounts(response) {
      let accountIds = response.data.data.attributes.data;
      for (let key in accountIds) {
        if (accountIds.hasOwnProperty(key) && /^0$|^[1-9]\d*$/.test(key) && key <= 4294967294) {
          this.accounts.push({
                               id: accountIds[key],
                               title: '',
                               url: '',
                               include: false,
                               current_balance: '0',
                               currency_code: 'EUR',
                               transactions: []
                             });
          this.loadSingleAccount(key, accountIds[key]);
        }
      }
    },
    loadSingleAccount(key, accountId) {
      axios.get('./api/v1/accounts/' + accountId)
          .then(response => {
                  let account = response.data.data;
                  if ('asset' === account.attributes.type || 'liabilities' === account.attributes.type) {
                    this.accounts[key].title = account.attributes.name;
                    this.accounts[key].url = './accounts/show/' + account.id;
                    this.accounts[key].current_balance = account.attributes.current_balance;
                    this.accounts[key].currency_code = account.attributes.currency_code;
                    this.accounts[key].include = true;
                    this.loadTransactions(key, accountId);
                  }
                }
          );
    },
    loadTransactions(key, accountId) {
      let startStr = this.start.toISOString().split('T')[0];
      let endStr = this.end.toISOString().split('T')[0];
      axios.get('./api/v1/accounts/' + accountId + '/transactions?page=1&limit=10&start=' + startStr + '&end=' + endStr)
          .then(response => {
                  this.accounts[key].transactions = response.data.data;
                  this.loading = false;
                  this.error = false;
                }
          );
    },
  }
}
</script>

<style scoped>

</style>
