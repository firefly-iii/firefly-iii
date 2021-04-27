<!--
  - Delete.vue
  - Copyright (c) 2021 james@firefly-iii.org
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
    <div class="col-lg-6 col-md-12 col-sm-12 col-xs-12 offset-lg-3">
      <div class="card card-default card-danger">
        <div class="card-header">
          <h3 class="card-title">
            <i class="fas fa-exclamation-triangle"></i>
            {{ $t('firefly.delete_account') }}
          </h3>
        </div>
        <!-- /.card-header -->
        <div class="card-body">
          <div class="callout callout-danger" v-if="!deleting && !deleted">
            <p>
              {{ $t('form.permDeleteWarning') }}
            </p>
          </div>
          <p v-if="!loading && !deleting && !deleted">
            {{ $t('form.account_areYouSure_js', {'name': this.accountName}) }}
          </p>
          <p v-if="!loading && !deleting && !deleted">
            <span v-if="piggyBankCount > 0">
              {{ $tc('form.also_delete_piggyBanks_js', piggyBankCount, {count: piggyBankCount}) }}
            </span>
            <span v-if="transactionCount > 0">
              {{ $tc('form.also_delete_transactions_js', transactionCount, {count: transactionCount}) }}
            </span>
          </p>
          <p v-if="transactionCount > 0 && !deleting && !deleted">
            {{ $tc('firefly.save_transactions_by_moving_js', transactionCount) }}
          </p>
          <p v-if="transactionCount > 0 && !deleting && !deleted">
            <select name="account" v-model="moveToAccount" class="form-control">
              <option :label="$t('firefly.none_in_select_list')" :value="0">{{ $t('firefly.none_in_select_list') }}</option>
              <option v-for="account in accounts" :label="account.name" :value="account.id">{{ account.name }}</option>
            </select>
          </p>

          <p v-if="loading || deleting || deleted" class="text-center">
            <i class="fas fa-spinner fa-spin"></i>
          </p>

        </div>
        <div class="card-footer">
          <button @click="deleteAccount" class="btn btn-danger float-right" v-if="!loading && !deleting && !deleted"> {{
              $t('firefly.delete_account')
            }}
          </button>
        </div>
      </div>

    </div>
  </div>
</template>

<script>
export default {
  name: "Delete",
  data() {
    return {
      loading: true,
      deleting: false,
      deleted: false,
      accountId: 0,
      accountName: '',
      piggyBankCount: 0,
      transactionCount: 0,
      moveToAccount: 0,
      accounts: []
    }
  },
  created() {
    let pathName = window.location.pathname;
    // console.log(pathName);
    let parts = pathName.split('/');
    this.accountId = parseInt(parts[parts.length - 1]);
    this.getAccount();
  },
  methods: {
    deleteAccount: function () {
      this.deleting = true;
      if (0 === this.moveToAccount) {
        this.execDeleteAccount();
      }
      if (0 !== this.moveToAccount) {
        // move to another account:
        this.moveTransactions();
      }
    },
    moveTransactions: function () {
      axios.post('./api/v1/data/bulk/accounts/transactions', {original_account: this.accountId, destination_account: this.moveToAccount}).then(response => {
        this.execDeleteAccount();
      });
    },
    execDeleteAccount: function () {
      axios.delete('./api/v1/accounts/' + this.accountId)
          .then(response => {
            this.deleted = true;
            this.deleting = false;
            window.location.href = (window.previousURL ?? '/') + '?account_id=' + this.accountId + '&message=deleted';
          });
    },
    getAccount: function () {
      axios.get('./api/v1/accounts/' + this.accountId)
          .then(response => {
                  let account = response.data.data;
                  this.accountName = account.attributes.name;
                  // now get piggy and transaction count
                  this.getPiggyBankCount(account.attributes.type, account.attributes.currency_code);
                }
          );
    },
    getAccounts: function (type, currencyCode) {
      axios.get('./api/v1/accounts?type=' + type)
          .then(response => {
                  let accounts = response.data.data;
                  for (let i in accounts) {
                    if (accounts.hasOwnProperty(i) && /^0$|^[1-9]\d*$/.test(i) && i <= 4294967294) {
                      let current = accounts[i];
                      if (false === current.attributes.active) {
                        continue;
                      }
                      if (currencyCode !== current.attributes.currency_code) {
                        continue;
                      }
                      if (this.accountId === parseInt(current.id)) {
                        continue;
                      }
                      this.accounts.push({id: current.id, name: current.attributes.name});
                    }
                  }
                  this.loading = false;
                }
          );
      // get accounts of the same type.
      // console.log('Go for "' + type + '"');
    },
    getPiggyBankCount: function (type, currencyCode) {
      axios.get('./api/v1/accounts/' + this.accountId + '/piggy_banks')
          .then(response => {
                  this.piggyBankCount = response.data.meta.pagination.total ? parseInt(response.data.meta.pagination.total) : 0;
                  this.getTransactionCount(type, currencyCode);
                }
          );
    },
    getTransactionCount: function (type, currencyCode) {
      axios.get('./api/v1/accounts/' + this.accountId + '/transactions')
          .then(response => {
                  this.transactionCount = response.data.meta.pagination.total ? parseInt(response.data.meta.pagination.total) : 0;
                  if (this.transactionCount > 0) {
                    this.getAccounts(type, currencyCode);
                  }
                  if (0 === this.transactionCount) {
                    this.loading = false;
                  }
                }
          );
    }
  }
}
</script>

<style scoped>

</style>