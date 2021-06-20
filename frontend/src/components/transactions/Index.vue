<!--
  - Index.vue
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
    <div class="row">
      <div class="col">
        <div class="card">
          <div class="card-body">
            Treemap categories?
          </div>
        </div>
      </div>
      <div class="col">
        <div class="card">
          <div class="card-body">
            Treemap accounts?
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
        <button @click="newCacheKey" class="btn btn-sm float-right btn-info"><span class="fas fa-sync"></span></button>
      </div>
    </div>
    <div class="row">
      <div class="col-lg-10 col-md-8 col-sm-12 col-xs-12">
        <div class="card">
          <div class="card-body p-0">
            <b-table id="my-table" small striped hover responsive="md" primary-key="key" :no-local-sorting="false"
                     :items="transactionRows"
                     :fields="fields"
                     :per-page="perPage"
                     sort-icon-left
                     ref="table"
                     :current-page="currentPage"
                     :busy.sync="loading"
                     :sort-desc.sync="sortDesc"
                     :sort-compare="tableSortCompare"
            >
              <template #table-busy>
                <span class="fa fa-spinner"></span>
              </template>
              <template #cell(type)="data">
                <span v-if="! data.item.split || data.item.split_parent === null">
                  <span class="fas fa-long-arrow-alt-right" v-if="'deposit' === data.item.type"></span>
                  <span class="fas fa-long-arrow-alt-left" v-else-if="'withdrawal' === data.item.type"></span>
                  <span class="fas fa-long-arrows-alt-h" v-else-if="'transfer' === data.item.type"></span>
                </span>
              </template>
              <template #cell(description)="data">
                <span class="fas fa-angle-right" v-if="data.item.split && data.item.split_parent !== null"></span>
                <a :class="false === data.item.active ? 'text-muted' : ''" :href="'./transactions/show/' + data.item.id" :title="data.value">{{
                    data.value
                  }}</a>
              </template>
              <template #cell(amount)="data">
                <span class="text-success" v-if="'deposit' === data.item.type">
                  {{ Intl.NumberFormat('en-US', {style: 'currency', currency: data.item.currency_code}).format(data.item.amount) }}
                </span>

                <span class="text-danger" v-else-if="'withdrawal' === data.item.type">
                  {{ Intl.NumberFormat('en-US', {style: 'currency', currency: data.item.currency_code}).format(-data.item.amount) }}
                </span>

                <span class="text-muted" v-else-if="'transfer' === data.item.type">
                  {{ Intl.NumberFormat('en-US', {style: 'currency', currency: data.item.currency_code}).format(data.item.amount) }}
                </span>
              </template>
              <template #cell(date)="data">
                {{ data.item.date_formatted }}
              </template>
              <template #cell(source_account)="data">
                <a :class="false === data.item.active ? 'text-muted' : ''" :href="'./accounts/show/' + data.item.source_id"
                   :title="data.item.source_name">{{ data.item.source_name }}</a>
              </template>
              <template #cell(destination_account)="data">
                <a :class="false === data.item.active ? 'text-muted' : ''" :href="'./accounts/show/' + data.item.destination_id"
                   :title="data.item.destination_name">{{ data.item.destination_name }}</a>
              </template>
              <template #cell(menu)="data">
                <div class="btn-group btn-group-sm" v-if="! data.item.split || data.item.split_parent === null">
                  <div class="dropdown">
                    <button class="btn btn-light btn-sm dropdown-toggle" type="button" :id="'dropdownMenuButton' + data.item.id" data-toggle="dropdown"
                            aria-haspopup="true" aria-expanded="false">
                      {{ $t('firefly.actions') }}
                    </button>
                    <div class="dropdown-menu" :aria-labelledby="'dropdownMenuButton' + data.item.id">
                      <a class="dropdown-item" :href="'./transactions/edit/' + data.item.id"><span class="fa fas fa-pencil-alt"></span> {{ $t('firefly.edit') }}</a>
                      <a class="dropdown-item" :href="'./transactions/delete/' + data.item.id"><span class="fa far fa-trash"></span> {{ $t('firefly.delete') }}</a>
                    </div>
                  </div>
                </div>
                <div class="btn btn-light btn-sm" v-if="data.item.split && data.item.split_parent === null && data.item.collapsed === true"
                     v-on:click="toggleCollapse(data.item)">
                  <span class="fa fa-caret-down"></span>
                  {{ $t('firefly.transaction_expand_split') }}
                </div>
                <div class="btn btn-light btn-sm" v-else-if="data.item.split && data.item.split_parent === null && data.item.collapsed === false"
                     v-on:click="toggleCollapse(data.item)">
                  <span class="fa fa-caret-up"></span>
                  {{ $t('firefly.transaction_collapse_split') }}
                </div>
              </template>
              <template #cell(category)="data">
                {{ data.item.category_name }}
              </template>
            </b-table>
          </div>
          <div class="card-footer">
            <a :href="'./transactions/create/' + type" class="btn btn-success"
               :title="$t('firefly.create_new_transaction')">{{ $t('firefly.create_new_transaction') }}</a>
          </div>
        </div>
      </div>
      <div class="col-lg-2 col-md-4">
        <div class="card">
          <div class="card-body">
            Box previous periods
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
        <button @click="newCacheKey" class="btn btn-sm float-right btn-info"><span class="fas fa-sync"></span></button>
      </div>
    </div>
  </div>
</template>

<script>

import {mapGetters, mapMutations} from "vuex";
import format from "date-fns/format";
import {configureAxios} from "../../shared/forageStore";

export default {
  name: "Index",
  data() {
    return {
      transactions: [],
      transactionRows: [],
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
      sortable: null
    }
  },
  watch: {
    storeReady: function () {
      this.getTransactionList();
    },
    start: function () {
      this.getTransactionList();
    },
    end: function () {
      this.getTransactionList();
    },
    activeFilter: function (value) {
      this.filterAccountList();
    }
  },
  computed: {
    ...mapGetters('root', ['listPageSize', 'cacheKey']),
    ...mapGetters('dashboard/index', ['start', 'end',]),
    'indexReady': function () {
      return null !== this.start && null !== this.end && null !== this.listPageSize && this.ready;
    },
    cardTitle: function () {
      return this.$t('firefly.' + this.type + '_transactions');
    }
  },
  created() {
    let pathName = window.location.pathname;
    let parts = pathName.split('/');
    this.type = parts[parts.length - 1];
    this.perPage = this.listPageSize ?? 51;
    console.log('Per page: ' + this.perPage);

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
    updateFieldList: function () {
      this.fields = [
        {key: 'type', label: ' ', sortable: false},
        {key: 'description', label: this.$t('list.description'), sortable: true},
        {key: 'amount', label: this.$t('list.amount'), sortable: true},
        {key: 'date', label: this.$t('list.date'), sortable: true},
        {key: 'source_name', label: this.$t('list.source_account'), sortable: true},
        {key: 'destination_name', label: this.$t('list.destination_account'), sortable: true},
        {key: 'category_name', label: this.$t('list.category'), sortable: true},
        {key: 'menu', label: ' ', sortable: false},
      ];
    },
    newCacheKey: function () {
      this.refreshCacheKey();
      this.downloaded = false;
      this.accounts = [];
      this.getTransactionList();
    },
    getTransactionList: function () {
      console.log('getTransactionList()');
      if (this.indexReady && !this.loading && !this.downloaded) {
        console.log('Index ready, not loading and not already downloaded. Reset.');
        this.loading = true;
        this.perPage = this.listPageSize ?? 51;
        this.transactions = [];
        this.transactionRows = [];
        this.downloadTransactionList(1);
      }
    },
    downloadTransactionList: function (page) {
      console.log('downloadTransactionList(' + page + ')');
      configureAxios().then(async (api) => {
        let startStr = format(this.start, 'y-MM-dd');
        let endStr = format(this.end, 'y-MM-dd');
        api.get('./api/v1/transactions?type=' + this.type + '&page=' + page + "&start=" + startStr + "&end=" + endStr + '&cache=' + this.cacheKey)
            .then(response => {

                    //let currentPage = parseInt(response.data.meta.pagination.current_page);
                    //let totalPages = parseInt(response.data.meta.pagination.total_pages);
                    this.total = parseInt(response.data.meta.pagination.total);
                    //console.log('total is ' + this.total);
                    this.transactions.push(...response.data.data);
                    // if (currentPage < totalPage) {
                    //   let nextPage = currentPage + 1;
                    //   this.downloadTransactionList(nextPage);
                    // }
                    // if (currentPage >= totalPage) {
                    // console.log('Looks like all downloaded.');
                    this.downloaded = true;
                    this.createTransactionRows();
                    // }

                  }
            );
      });
    },
    createTransactionRows: function () {
      this.transactionRows = [];
      for (let i in this.transactions) {
        let transaction = this.transactions[i];
        let transactionRow = this.getTransactionRow(transaction, 0);
        this.transactionRows.push(transactionRow);

        if (transaction.attributes.transactions.length > 1) {
          transactionRow.description = transaction.attributes.group_title;
          transactionRow.split = true;
          transactionRow.collapsed = transaction.collapsed === true || transaction.collapsed === undefined;
          transactionRow.amount = transaction.attributes.transactions
              .map(transaction => Number(transaction.amount))
              .reduce((sum, n) => sum + n);
          transactionRow.source_name = '';
          transactionRow.source_id = '';
          transactionRow.destination_name = '';
          transactionRow.destination_id = '';

          if (!transactionRow.collapsed) {
            for (let i = 0; i < transaction.attributes.transactions.length; i++) {
              let splitTransactionRow = this.getTransactionRow(transaction, i);
              splitTransactionRow.key = splitTransactionRow.id + "." + i
              splitTransactionRow.split = true;
              splitTransactionRow.split_index = i + 1;
              splitTransactionRow.split_parent = transactionRow;
              this.transactionRows.push(splitTransactionRow);
            }
          }
        }
      }

      this.loading = false;
    },
    getTransactionRow(transaction, index) {
      let transactionRow = {};
      let currentTransaction = transaction.attributes.transactions[index];

      transactionRow.key = transaction.id;
      transactionRow.id = transaction.id;
      transactionRow.type = currentTransaction.type;
      transactionRow.description = currentTransaction.description;
      transactionRow.amount = currentTransaction.amount;
      transactionRow.currency_code = currentTransaction.currency_code;
      transactionRow.date = new Date(currentTransaction.date);
      transactionRow.date_formatted = format(transactionRow.date, this.$t('config.month_and_day_fns'));
      transactionRow.source_name = currentTransaction.source_name;
      transactionRow.source_id = currentTransaction.source_id;
      transactionRow.destination_name = currentTransaction.destination_name;
      transactionRow.destination_id = currentTransaction.destination_id;
      transactionRow.category_id = currentTransaction.category_id;
      transactionRow.category_name = currentTransaction.category_name;
      transactionRow.split = false;
      transactionRow.split_index = 0;
      transactionRow.split_parent = null;

      return transactionRow;
    },
    toggleCollapse: function (row) {
      let transaction = this.transactions.filter(transaction => transaction.id === row.id)[0];
      if (transaction.collapsed === undefined) {
        transaction.collapsed = false;
      } else {
        transaction.collapsed = !transaction.collapsed;
      }
      this.createTransactionRows();
    },
    tableSortCompare: function (aRow, bRow, key, sortDesc, formatter, compareOptions, compareLocale) {
      let a = aRow[key]
      let b = bRow[key]

      if (aRow.id === bRow.id) {
        // Order split transactions normally when compared to each other, except always put the header first
        if (aRow.split_parent === null) {
          return sortDesc ? 1 : -1;
        } else if (bRow.split_parent === null) {
          return sortDesc ? -1 : 1;
        }
      } else {
        // Sort split transactions based on their parent when compared to other transactions
        if (aRow.split && aRow.split_parent !== null) {
          a = aRow.split_parent[key]
        }
        if (bRow.split && bRow.split_parent !== null) {
          b = bRow.split_parent[key]
        }
      }

      if (
          (typeof a === 'number' && typeof b === 'number') ||
          (a instanceof Date && b instanceof Date)
      ) {
        // If both compared fields are native numbers or both are native dates
        return a < b ? -1 : a > b ? 1 : 0
      } else {
        // Otherwise stringify the field data and use String.prototype.localeCompare
        return toString(a).localeCompare(toString(b), compareLocale, compareOptions)
      }

      function toString(value) {
        if (value === null || typeof value === 'undefined') {
          return ''
        } else if (value instanceof Object) {
          return Object.keys(value)
              .sort()
              .map(key => toString(value[key]))
              .join(' ')
        } else {
          return String(value)
        }
      }
    },
  },
}
</script>