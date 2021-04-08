<!--
  - Index.vue
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
    <div class="row">
      <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
        <b-pagination
            v-model="currentPage"
            :total-rows="total"
            :per-page="perPage"
            aria-controls="my-table"
        ></b-pagination>
      </div>
    </div>
    <div class="row">
      <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
        <div class="card">
          <div class="card-header">
          </div>
          <div class="card-body p-0">
            <b-table id="my-table" striped hover primary-key="id"
                     :items="accounts" :fields="fields"
                     :per-page="perPage"
                     sort-icon-left
                     ref="table"
                     :current-page="currentPage"
                     :busy.sync="loading"
                     :sort-by.sync="sortBy"
                     :sort-desc.sync="sortDesc"
            >
              <template #cell(title)="data">
                <a :class="false === data.item.active ? 'text-muted' : ''" :href="'./accounts/show/' + data.item.id" :title="data.value">{{ data.value }}</a>
              </template>
              <template #cell(number)="data">
                <span v-if="null !== data.item.iban && null === data.item.account_number">{{ data.item.iban }}</span>
                <span v-if="null === data.item.iban && null !== data.item.account_number">{{ data.item.account_number }}</span>
                <span v-if="null !== data.item.iban && null !== data.item.account_number">{{ data.item.iban }} ({{ data.item.account_number }})</span>
              </template>
              <template #cell(current_balance)="data">
                <span class="text-success" v-if="parseFloat(data.item.current_balance) > 0">
                  {{
                    Intl.NumberFormat('en-US', {
                      style: 'currency', currency:
                      data.item.currency_code
                    }).format(data.item.current_balance)
                  }}
                </span>
                <span class="text-danger" v-if="parseFloat(data.item.current_balance) < 0">
                  {{
                    Intl.NumberFormat('en-US', {
                      style: 'currency', currency:
                      data.item.currency_code
                    }).format(data.item.current_balance)
                  }}
                </span>

                <span class="text-muted" v-if="0 === parseFloat(data.item.current_balance)">
                  {{
                    Intl.NumberFormat('en-US', {
                      style: 'currency', currency:
                      data.item.currency_code
                    }).format(data.item.current_balance)
                  }}
                </span>
                <span v-if="'asset' === type && 'loading' === data.item.balance_diff">
                  <i class="fas fa-spinner fa-spin"></i>
                </span>
                <span v-if="'asset' === type && 'loading' !== data.item.balance_diff">
                   (<span class="text-success" v-if="parseFloat(data.item.balance_diff) > 0">{{
                    Intl.NumberFormat('en-US', {
                      style: 'currency', currency:
                      data.item.currency_code
                    }).format(data.item.balance_diff)
                  }}</span><span class="text-muted" v-if="0===parseFloat(data.item.balance_diff)">{{
                    Intl.NumberFormat('en-US', {
                      style: 'currency', currency:
                      data.item.currency_code
                    }).format(data.item.balance_diff)
                  }}</span><span class="text-danger" v-if="parseFloat(data.item.balance_diff) < 0">{{
                    Intl.NumberFormat('en-US', {
                      style: 'currency', currency:
                      data.item.currency_code
                    }).format(data.item.balance_diff)
                  }}</span>)
                </span>
              </template>
              <template #cell(menu)="data">
                <div class="btn-group btn-group-sm">
                  <div class="dropdown">
                    <button class="btn btn-light btn-sm dropdown-toggle" type="button" :id="'dropdownMenuButton' + data.item.id" data-toggle="dropdown"
                            aria-haspopup="true" aria-expanded="false">
                      {{ $t('firefly.actions') }}
                    </button>
                    <div class="dropdown-menu" :aria-labelledby="'dropdownMenuButton' + data.item.id">
                      <a class="dropdown-item" :href="'./accounts/edit/' + data.item.id"><i class="fa fas fa-pencil-alt"></i> {{ $t('firefly.edit') }}</a>
                      <a class="dropdown-item" :href="'./accounts/delete/' + data.item.id"><i class="fa far fa-trash"></i> {{ $t('firefly.delete') }}</a>
                      <a v-if="'asset' === type" class="dropdown-item" :href="'./accounts/reconcile/' + data.item.id + '/index'"><i class="fas fa-check"></i>
                        {{ $t('firefly.reconcile_this_account') }}</a>
                    </div>
                  </div>
                </div>
              </template>
            </b-table>
          </div>
          <div class="card-footer">
            <a :href="'./accounts/create/' + type" class="btn btn-success" :title="$t('firefly.create_new_' + type)">{{ $t('firefly.create_new_' + type) }}</a>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>

import {mapGetters} from "vuex";
import Sortable from "sortablejs";

export default {
  name: "Index",
  props: {
    accountTypes: String
  },
  data() {
    return {
      accounts: [],
      allAccounts: [],
      type: 'all',
      downloaded: false,
      loading: false,
      ready: false,
      fields: [],
      currentPage: 1,
      perPage: 5,
      total: 0,
      sortBy: 'order',
      sortDesc: false,
      sortableOptions: {
        disabled: false,
        chosenClass: 'is-selected',
        onEnd: null
      },
      sortable: null
    }
  },
  watch: {
    storeReady: function () {
      this.getAccountList();
    },
    start: function () {
      this.getAccountList();
    },
    end: function () {
      this.getAccountList();
    },
    orderMode: function (value) {
      // update the table headers
      this.updateFieldList();

      // reorder the accounts:
      this.reorderAccountList(value);

      // make table sortable:
      this.makeTableSortable(value);
    },
    activeFilter: function (value) {
      this.filterAccountList();
    }
  },
  computed: {
    ...mapGetters('root', ['listPageSize']),
    ...mapGetters('accounts/index', ['orderMode', 'activeFilter']),
    ...mapGetters('dashboard/index', ['start', 'end',]),
    'indexReady': function () {
      return null !== this.start && null !== this.end && null !== this.listPageSize && this.ready;
    },
    cardTitle: function () {
      return this.$t('firefly.' + this.type + '_accounts');
    }
  },
  created() {
    let pathName = window.location.pathname;
    let parts = pathName.split('/');
    this.type = parts[parts.length - 1];

    let params = new URLSearchParams(window.location.search);
    this.currentPage = params.get('page') ? parseInt(params.get('page')) : 1;
    this.updateFieldList();
    this.ready = true;
  },

  methods: {
    saveAccountSort: function (event) {
      let oldIndex = parseInt(event.oldIndex);
      let newIndex = parseInt(event.newIndex);
      let identifier = parseInt(event.item.attributes.getNamedItem('data-pk').value);
      for (let i in this.accounts) {
        if (this.accounts.hasOwnProperty(i) && /^0$|^[1-9]\d*$/.test(i) && i <= 4294967294) {
          let current = this.accounts[i];

          // the actual account
          if (current.id === identifier) {
            let newOrder = parseInt(current.order) + (newIndex - oldIndex);
            this.accounts[i].order = newOrder;
            let url = './api/v1/accounts/' + current.id;
            axios.put(url, {order: newOrder}).then(response => {
              // TODO should update local account list, not refresh the whole thing.
              this.getAccountList();
            });
          }
        }
      }
    },
    reorderAccountList: function (orderMode) {
      if (orderMode) {
        this.sortBy = 'order';
        this.sortDesc = false;
      }
    },
    makeTableSortable: function (orderMode) {
      this.sortableOptions.disabled = !orderMode;
      this.sortableOptions.onEnd = this.saveAccountSort;

      // make sortable of table:
      if (null === this.sortable) {
        this.sortable = Sortable.create(this.$refs.table.$el.querySelector('tbody'), this.sortableOptions);
      }
      this.sortable.option('disabled', this.sortableOptions.disabled);
    },

    updateFieldList: function () {
      this.fields = [];

      this.fields = [{key: 'title', label: this.$t('list.name'), sortable: !this.orderMode}];
      if ('asset' === this.type) {
        this.fields.push({key: 'role', label: this.$t('list.role'), sortable: !this.orderMode});
      }
      // add the rest
      this.fields.push({key: 'number', label: this.$t('list.iban'), sortable: !this.orderMode});
      this.fields.push({key: 'current_balance', label: this.$t('list.currentBalance'), sortable: !this.orderMode});
      this.fields.push({key: 'menu', label: ' ', sortable: false});
    },
    getAccountList: function () {
      console.log('getAccountList()');
      if (this.indexReady && !this.loading && !this.downloaded) {
        console.log('Index ready, not loading and not already downloaded. Reset.');
        this.loading = true;
        this.perPage = this.listPageSize ?? 51;
        this.accounts = [];
        this.allAccounts = [];
        this.downloadAccountList(1);
      }
      if (this.indexReady && !this.loading && this.downloaded) {
        console.log('Index ready, not loading and not downloaded.');
        this.loading = true;
        this.filterAccountList();
        // TODO filter accounts.
      }
    },
    downloadAccountList: function (page) {
      console.log('downloadAccountList(' + page + ')');
      axios.get('./api/v1/accounts?type=' + this.type + '&page=' + page)
          .then(response => {
                  let currentPage = parseInt(response.data.meta.pagination.current_page);
                  let totalPage = parseInt(response.data.meta.pagination.total_pages);
                  this.total = parseInt(response.data.meta.pagination.total);
                  this.parseAccounts(response.data.data);
                  if (currentPage < totalPage) {
                    let nextPage = currentPage + 1;
                    this.downloadAccountList(nextPage);
                  }
                  if (currentPage >= totalPage) {
                    console.log('Looks like all downloaded.');
                    this.downloaded = true;
                    this.filterAccountList();
                  }
                }
          );
    },
    filterAccountList: function () {
      console.log('filterAccountList()');
      this.accounts = [];
      for (let i in this.allAccounts) {
        if (this.allAccounts.hasOwnProperty(i) && /^0$|^[1-9]\d*$/.test(i) && i <= 4294967294) {
          // 1 = active only
          // 2 = inactive only
          // 3 = both
          if (1 === this.activeFilter && false === this.allAccounts[i].active) {
            console.log('Skip account #' + this.allAccounts[i].id + ' because not active.');
            continue;
          }
          if (2 === this.activeFilter && true === this.allAccounts[i].active) {
            console.log('Skip account #' + this.allAccounts[i].id + ' because active.');
            continue;
          }
          console.log('Include account #' + this.allAccounts[i].id + '.');

          this.accounts.push(this.allAccounts[i]);
        }
      }
      this.total = this.accounts.length;
      this.loading = false;
    },
    roleTranslate: function (role) {
      if (null === role) {
        return '';
      }
      return this.$t('firefly.account_role_' + role);
    },
    parsePages: function (data) {
      this.total = parseInt(data.pagination.total);
      //console.log('Total is now ' + this.total);
    },
    parseAccounts: function (data) {
      console.log('In parseAccounts()');
      for (let key in data) {
        if (data.hasOwnProperty(key) && /^0$|^[1-9]\d*$/.test(key) && key <= 4294967294) {
          let current = data[key];
          let acct = {};
          acct.id = parseInt(current.id);
          acct.order = current.attributes.order;
          acct.title = current.attributes.name;
          acct.active = current.attributes.active;
          acct.role = this.roleTranslate(current.attributes.account_role);
          acct.account_number = current.attributes.account_number;
          acct.current_balance = current.attributes.current_balance;
          acct.currency_code = current.attributes.currency_code;
          acct.balance_diff = 'loading';

          if (null !== current.attributes.iban) {
            acct.iban = current.attributes.iban.match(/.{1,4}/g).join(' ');
          }

          this.allAccounts.push(acct);
          if ('asset' === this.type) {
            this.getAccountBalanceDifference(this.allAccounts.length - 1, current);
          }
        }
      }
    },
    getAccountBalanceDifference: function (index, acct) {
      console.log('getAccountBalanceDifference(' + index + ')');
      // get account on day 0
      let promises = [];

      // add meta data to promise context.
      promises.push(new Promise((resolve) => {
        resolve(
            {
              account: acct,
              index: index,
            }
        );
      }));

      promises.push(axios.get('./api/v1/accounts/' + acct.id + '?date=' + this.start.toISOString().split('T')[0]));
      promises.push(axios.get('./api/v1/accounts/' + acct.id + '?date=' + this.end.toISOString().split('T')[0]));

      Promise.all(promises).then(responses => {
        let index = responses[0].index;
        let startBalance = parseFloat(responses[1].data.data.attributes.current_balance);
        let endBalance = parseFloat(responses[2].data.data.attributes.current_balance);
        this.allAccounts[index].balance_diff = endBalance - startBalance;
      });
    },
  }
}
</script>

<style scoped>

</style>
