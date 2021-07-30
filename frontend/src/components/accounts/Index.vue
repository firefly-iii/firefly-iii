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
      <div class="col-lg-8 col-md-6 col-sm-12 col-xs-12">
        <b-pagination
            v-model="currentPage"
            :total-rows="total"
            :per-page="perPage"
            aria-controls="my-table"
        ></b-pagination>
      </div>
      <div class="col-lg-4 col-md-6 col-sm-12 col-xs-12">
        <a :href="'./accounts/create/' + type" class="btn btn-sm mb-2 float-right btn-success" :title="$t('firefly.create_new_' + type)"><span class="fas fa-plus"></span> {{ $t('firefly.create_new_' + type) }}</a>
        <button @click="newCacheKey" class="btn btn-sm mb-2 mr-2 float-right btn-info"><span class="fas fa-sync"></span></button>
      </div>
    </div>
    <div class="row">
      <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
        <div class="card">
          <div class="card-body p-0">
            <b-table id="my-table" striped hover responsive="md" primary-key="id" :no-local-sorting="false"
                     :items="accounts" :fields="fields"
                     :per-page="perPage"
                     sort-icon-left
                     ref="table"
                     :current-page="currentPage"
                     :busy.sync="loading"
                     :sort-by.sync="sortBy"
                     :sort-desc.sync="sortDesc"
            >
              <template #table-busy>
                <span class="fas fa-spinner fa-spin"></span>
              </template>
              <template #cell(name)="data">
                <a :class="false === data.item.active ? 'text-muted' : ''" :href="'./accounts/show/' + data.item.id" :title="data.value">{{ data.value }}</a>
              </template>
              <template #cell(acct_number)="data">
                {{ data.item.acct_number }}
              </template>
              <template #cell(last_activity)="data">
                <span v-if="'asset' === type && 'loading' === data.item.last_activity">
                  <span class="fas fa-spinner fa-spin"></span>
                </span>
                <span v-if="'asset' === type && 'none' === data.item.last_activity" class="text-muted">
                  {{ $t('firefly.never') }}
                </span>
                <span v-if="'asset' === type && 'loading' !== data.item.last_activity && 'none' !== data.item.last_activity">
                  {{ data.item.last_activity }}
                </span>
              </template>
              <template #cell(amount_due)="data">
                <span class="text-success" v-if="parseFloat(data.item.amount_due) > 0">
                  {{ Intl.NumberFormat(locale, {style: 'currency', currency: data.item.currency_code}).format(data.item.amount_due) }}
                </span>

                <span class="text-danger" v-if="parseFloat(data.item.amount_due) < 0">
                  {{ Intl.NumberFormat(locale, {style: 'currency', currency: data.item.currency_code}).format(data.item.amount_due) }}
                </span>

                <span class="text-muted" v-if="parseFloat(data.item.amount_due) === 0.0">
                  {{ Intl.NumberFormat(locale, {style: 'currency', currency: data.item.currency_code}).format(data.item.amount_due) }}
                </span>

              </template>
              <template #cell(current_balance)="data">
                <span class="text-success" v-if="parseFloat(data.item.current_balance) > 0">
                  {{
                    Intl.NumberFormat(locale, {
                      style: 'currency', currency:
                      data.item.currency_code
                    }).format(data.item.current_balance)
                  }}
                </span>
                <span class="text-danger" v-if="parseFloat(data.item.current_balance) < 0">
                  {{
                    Intl.NumberFormat(locale, {
                      style: 'currency', currency:
                      data.item.currency_code
                    }).format(data.item.current_balance)
                  }}
                </span>

                <span class="text-muted" v-if="0 === parseFloat(data.item.current_balance)">
                  {{
                    Intl.NumberFormat(locale, {
                      style: 'currency', currency:
                      data.item.currency_code
                    }).format(data.item.current_balance)
                  }}
                </span>
                <span v-if="'asset' === type && 'loading' === data.item.balance_diff">
                  <span class="fas fa-spinner fa-spin"></span>
                </span>
                <span v-if="'asset' === type && 'loading' !== data.item.balance_diff">
                   (<span class="text-success" v-if="parseFloat(data.item.balance_diff) > 0">{{
                    Intl.NumberFormat(locale, {
                      style: 'currency', currency:
                      data.item.currency_code
                    }).format(data.item.balance_diff)
                  }}</span><span class="text-muted" v-if="0===parseFloat(data.item.balance_diff)">{{
                    Intl.NumberFormat(locale, {
                      style: 'currency', currency:
                      data.item.currency_code
                    }).format(data.item.balance_diff)
                  }}</span><span class="text-danger" v-if="parseFloat(data.item.balance_diff) < 0">{{
                    Intl.NumberFormat(locale, {
                      style: 'currency', currency:
                      data.item.currency_code
                    }).format(data.item.balance_diff)
                  }}</span>)
                </span>
              </template>
              <template #cell(interest)="data">
                {{ parseFloat(data.item.interest) }}% ({{ data.item.interest_period }})
              </template>
              <template #cell(menu)="data">
                <div class="btn-group btn-group-sm">
                  <div class="dropdown">
                    <button class="btn btn-light btn-sm dropdown-toggle" type="button" :id="'dropdownMenuButton' + data.item.id" data-toggle="dropdown"
                            aria-haspopup="true" aria-expanded="false">
                      {{ $t('firefly.actions') }}
                    </button>
                    <div class="dropdown-menu" :aria-labelledby="'dropdownMenuButton' + data.item.id">
                      <a class="dropdown-item" :href="'./accounts/edit/' + data.item.id"><span class="fa fas fa-pencil-alt"></span> {{ $t('firefly.edit') }}</a>
                      <a class="dropdown-item" :href="'./accounts/delete/' + data.item.id"><span class="fa far fa-trash"></span> {{ $t('firefly.delete') }}</a>
                      <a v-if="'asset' === type" class="dropdown-item" :href="'./accounts/reconcile/' + data.item.id + '/index'"><span
                          class="fas fa-check"></span>
                        {{ $t('firefly.reconcile_this_account') }}</a>
                    </div>
                  </div>
                </div>
              </template>
            </b-table>
          </div>
        </div>
      </div>
    </div>
    <div class="row">
      <div class="col-lg-8 col-md-6 col-sm-12 col-xs-12">
        <b-pagination
            v-model="currentPage"
            :total-rows="total"
            :per-page="perPage"
            aria-controls="my-table"
        ></b-pagination>
      </div>
      <div class="col-lg-4 col-md-6 col-sm-12 col-xs-12">
        <a :href="'./accounts/create/' + type" class="btn btn-sm mt-2 float-right btn-success" :title="$t('firefly.create_new_' + type)"><span class="fas fa-plus"></span> {{ $t('firefly.create_new_' + type) }}</a>
        <button @click="newCacheKey" class="btn btn-sm mt-2 mr-2 float-right btn-info"><span class="fas fa-sync"></span></button>
      </div>
    </div>
  </div>
</template>

<script>

import {mapGetters, mapMutations} from "vuex";
import Sortable from "sortablejs";
import format from "date-fns/format";
// import {setup} from 'axios-cache-adapter';
// import {cacheAdapterEnhancer} from 'axios-extensions';
import {configureAxios} from "../../shared/forageStore";


// get all and cache, dont keep the table busy.

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
      total: 1,
      sortBy: 'order',
      sortDesc: false,
      api: null,
      sortableOptions: {
        disabled: false,
        chosenClass: 'is-selected',
        onEnd: null
      },
      sortable: null,
      locale: 'en-US'
    }
  },
  watch: {
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
    ...mapGetters('root', ['listPageSize', 'cacheKey']),
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
    this.locale = localStorage.locale ?? 'en-US';
    let pathName = window.location.pathname;
    let parts = pathName.split('/');
    this.type = parts[parts.length - 1];
    this.perPage = this.listPageSize ?? 51;
    // console.log('Per page: ' + this.perPage);

    let params = new URLSearchParams(window.location.search);
    this.currentPage = params.get('page') ? parseInt(params.get('page')) : 1;
    this.updateFieldList();
    this.ready = true;

    // make object thing:
    // let token = document.head.querySelector('meta[name="csrf-token"]');
    // this.api = setup(
    //     {
    //       // `axios` options
    //       //baseURL: './',
    //       headers: {'X-CSRF-TOKEN': token.content, 'X-James': 'yes'},
    //
    //       // `axios-cache-adapter` options
    //       cache: {
    //         maxAge: 15 * 60 * 1000,
    //         readHeaders: false,
    //         exclude: {
    //           query: false,
    //         },
    //         debug: true
    //       }
    //     });
  },

  methods: {
    ...mapMutations('root', ['refreshCacheKey',]),
    // itemsProvider: function (ctx, callback) {
    //   console.log('itemsProvider()');
    //   console.log('ctx.currentPage = ' + ctx.currentPage);
    //   console.log('this.currentPage = ' + this.currentPage);
    //   if (ctx.currentPage === this.currentPage) {
    //     let direction = this.sortDesc ? '-' : '+';
    //     let url = 'api/v1/accounts?type=' + this.type + '&page=' + ctx.currentPage + '&sort=' + direction + this.sortBy;
    //     this.api.get(url)
    //         .then(async (response) => {
    //                 this.total = parseInt(response.data.meta.pagination.total);
    //                 let items = this.parseAccountsAndReturn(response.data.data);
    //                 items = this.filterAccountListAndReturn(items);
    //                 callback(items);
    //               }
    //         );
    //     return null;
    //   }
    //   return [];
    // },

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
// See reference nr. 8
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
    newCacheKey: function () {
      this.refreshCacheKey();
      this.downloaded = false;
      this.accounts = [];
      this.getAccountList();
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
      this.fields = [{key: 'name', label: this.$t('list.name'), sortable: !this.orderMode}];
      if ('asset' === this.type) {
        this.fields.push({key: 'role', label: this.$t('list.role'), sortable: !this.orderMode});
      }
      if ('liabilities' === this.type) {
        this.fields.push({key: 'liability_type', label: this.$t('list.liability_type'), sortable: !this.orderMode});
        this.fields.push({key: 'liability_direction', label: this.$t('list.liability_direction'), sortable: !this.orderMode});
        this.fields.push({key: 'interest', label: this.$t('list.interest') + ' (' + this.$t('list.interest_period') + ')', sortable: !this.orderMode});
      }
      // add the rest
      this.fields.push({key: 'acct_number', label: this.$t('list.iban'), sortable: !this.orderMode});
      this.fields.push({key: 'current_balance', label: this.$t('list.currentBalance'), sortable: !this.orderMode});
      if ('liabilities' === this.type) {
        this.fields.push({key: 'amount_due', label: this.$t('firefly.left_in_debt'), sortable: !this.orderMode});
      }
      if ('asset' === this.type || 'liabilities' === this.type) {
        this.fields.push({key: 'last_activity', label: this.$t('list.lastActivity'), sortable: !this.orderMode});
      }
      this.fields.push({key: 'menu', label: ' ', sortable: false});
    },
    getAccountList: function () {
      // console.log('getAccountList()');
      if (this.indexReady && !this.loading && !this.downloaded) {
        // console.log('Index ready, not loading and not already downloaded. Reset.');
        this.loading = true;
        this.perPage = this.listPageSize ?? 51;
        this.accounts = [];
        this.allAccounts = [];
        this.downloadAccountList(1);
      }
      if (this.indexReady && !this.loading && this.downloaded) {
        // console.log('Index ready, not loading and not downloaded.');
        this.loading = true;
        this.filterAccountList();
      }
    },
    downloadAccountList: function (page) {
      // console.log('downloadAccountList(' + page + ')');
      configureAxios().then(async (api) => {
        api.get('./api/v1/accounts?type=' + this.type + '&page=' + page + '&key=' + this.cacheKey)
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
                      // console.log('Looks like all downloaded.');
                      this.downloaded = true;
                      this.filterAccountList();
                    }
                  }
            );
      });
    },
    filterAccountListAndReturn: function (allAccounts) {
      // console.log('filterAccountListAndReturn()');
      let accounts = [];
      for (let i in allAccounts) {
        if (allAccounts.hasOwnProperty(i) && /^0$|^[1-9]\d*$/.test(i) && i <= 4294967294) {
          // 1 = active only
          // 2 = inactive only
          // 3 = both
          if (1 === this.activeFilter && false === allAccounts[i].active) {
            // console.log('Skip account #' + this.allAccounts[i].id + ' because not active.');
            continue;
          }
          if (2 === this.activeFilter && true === allAccounts[i].active) {
            // console.log('Skip account #' + this.allAccounts[i].id + ' because active.');
            continue;
          }
          // console.log('Include account #' + this.allAccounts[i].id + '.');

          accounts.push(allAccounts[i]);
        }
      }
      return accounts;
    },
    filterAccountList: function () {
      // console.log('filterAccountList()');
      this.accounts = [];
      for (let i in this.allAccounts) {
        if (this.allAccounts.hasOwnProperty(i) && /^0$|^[1-9]\d*$/.test(i) && i <= 4294967294) {
          // 1 = active only
          // 2 = inactive only
          // 3 = both
          if (1 === this.activeFilter && false === this.allAccounts[i].active) {
            // console.log('Skip account #' + this.allAccounts[i].id + ' because not active.');
            continue;
          }
          if (2 === this.activeFilter && true === this.allAccounts[i].active) {
            // console.log('Skip account #' + this.allAccounts[i].id + ' because active.');
            continue;
          }
          // console.log('Include account #' + this.allAccounts[i].id + '.');

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
      // console.log('Total is now ' + this.total);
    },
    // parseAccountsAndReturn: function (data) {
    //   console.log('In parseAccountsAndReturn()');
    //   let allAccounts = [];
    //   for (let key in data) {
    //     if (data.hasOwnProperty(key) && /^0$|^[1-9]\d*$/.test(key) && key <= 4294967294) {
    //       let current = data[key];
    //       let acct = {};
    //       acct.id = parseInt(current.id);
    //       acct.order = current.attributes.order;
    //       acct.name = current.attributes.name;
    //       acct.active = current.attributes.active;
    //       acct.role = this.roleTranslate(current.attributes.account_role);
    //       acct.account_number = current.attributes.account_number;
    //       acct.current_balance = current.attributes.current_balance;
    //       acct.currency_code = current.attributes.currency_code;
    //
    //       if ('liabilities' === this.type) {
    //         acct.liability_type = this.$t('firefly.account_type_' + current.attributes.liability_type);
    //         acct.liability_direction = this.$t('firefly.liability_direction_' + current.attributes.liability_direction + '_short');
    //         acct.interest = current.attributes.interest;
    //         acct.interest_period = this.$t('firefly.interest_calc_' + current.attributes.interest_period);
    //         acct.amount_due = current.attributes.current_debt;
    //       }
    //       acct.balance_diff = 'loading';
    //       acct.last_activity = 'loading';
    //
    //       if (null !== current.attributes.iban) {
    //         acct.iban = current.attributes.iban.match(/.{1,4}/g).join(' ');
    //       }
    //       if (null === current.attributes.iban) {
    //         acct.iban = null;
    //       }
    //
    //       allAccounts.push(acct);
    //       if ('asset' === this.type) {
// See reference nr. 9
    //         //this.getAccountBalanceDifference(this.allAccounts.length - 1, current);
    //         //this.getAccountLastActivity(this.allAccounts.length - 1, current);
    //       }
    //     }
    //   }
    //   return allAccounts;
    // },
    parseAccounts: function (data) {
      // console.log('In parseAccounts()');
      for (let key in data) {
        if (data.hasOwnProperty(key) && /^0$|^[1-9]\d*$/.test(key) && key <= 4294967294) {
          let current = data[key];
          let acct = {};
          acct.id = parseInt(current.id);
          acct.order = current.attributes.order;
          acct.name = current.attributes.name;
          acct.active = current.attributes.active;
          acct.role = this.roleTranslate(current.attributes.account_role);

          // account number in 'acct_number'
          acct.acct_number = '';
          let iban = null;
          let acctNr = null;
          acct.acct_number = '';
          if (null !== current.attributes.iban) {
            iban = current.attributes.iban.match(/.{1,4}/g).join(' ');
          }
          if (null !== current.attributes.account_number) {
            acctNr = current.attributes.account_number;
          }
          // only account nr
          if (null === iban && null !== acctNr) {
            acct.acct_number = acctNr;
          }
          // only iban
          if (null !== iban && null === acctNr) {
            acct.acct_number = iban;
          }
          // both:
          if (null !== iban && null !== acctNr) {
            acct.acct_number = iban + ' (' + acctNr + ')';
          }


          acct.current_balance = current.attributes.current_balance;
          acct.currency_code = current.attributes.currency_code;

          if ('liabilities' === this.type) {
            acct.liability_type = this.$t('firefly.account_type_' + current.attributes.liability_type);
            acct.liability_direction = this.$t('firefly.liability_direction_' + current.attributes.liability_direction + '_short');
            acct.interest = current.attributes.interest;
            acct.interest_period = this.$t('firefly.interest_calc_' + current.attributes.interest_period);
            acct.amount_due = current.attributes.current_debt;
          }
          acct.balance_diff = 'loading';
          acct.last_activity = 'loading';

          this.allAccounts.push(acct);
          if ('asset' === this.type) {
            this.getAccountBalanceDifference(this.allAccounts.length - 1, current);
            this.getAccountLastActivity(this.allAccounts.length - 1, current);
          }
        }
      }
    },
    getAccountLastActivity: function (index, acct) {
      // console.log('getAccountLastActivity(' + index + ')');
      // get single transaction for account:
      //  /api/v1/accounts/1/transactions?limit=1
      configureAxios().then(async (api) => {
        api.get('./api/v1/accounts/' + acct.id + '/transactions?limit=1&key=' + this.cacheKey).then(response => {
          if (0 === response.data.data.length) {
            this.allAccounts[index].last_activity = 'none';
            return;
          }
          let date = new Date(response.data.data[0].attributes.transactions[0].date);
          this.allAccounts[index].last_activity = format(date, this.$t('config.month_and_day_fns'));
        });
      });
    },
    getAccountBalanceDifference: function (index, acct) {
      // console.log('getAccountBalanceDifference(' + index + ')');
      // get account on day 0
      let promises = [];

      // add meta data to promise context.
      promises.push(Promise.resolve({
                                      account: acct,
                                      index: index,
                                    }));

      let startStr = format(this.start, 'y-MM-dd');
      let endStr = format(this.end, 'y-MM-dd');

      configureAxios().then(api => {
        return api.get('./api/v1/accounts/' + acct.id + '?date=' + startStr + '&key=' + this.cacheKey);
      });

      //promises.push(axios.get('./api/v1/accounts/' + acct.id + '?date=' + startStr + '&key=' + this.cacheKey));
      promises.push(configureAxios().then(api => {
        return api.get('./api/v1/accounts/' + acct.id + '?date=' + startStr + '&key=' + this.cacheKey);
      }));
      promises.push(configureAxios().then(api => {
        return api.get('./api/v1/accounts/' + acct.id + '?date=' + endStr + '&key=' + this.cacheKey);
      }));

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
