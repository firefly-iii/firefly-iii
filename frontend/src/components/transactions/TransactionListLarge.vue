<!--
  - TransactionListLarge.vue
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
        <BPagination v-if="!loading"
                     v-model="currentPage"
                     :total-rows="total"
                     :per-page="perPage"
                     aria-controls="my-table"
        ></BPagination>
      </div>
      <div class="col-lg-4 col-md-6 col-sm-12 col-xs-12">
        <button @click="newCacheKey" class="btn btn-sm float-right btn-info"><span class="fas fa-sync"></span></button>
      </div>
    </div>
    <div class="row">
      <div class="col">
        <div class="card">
          <div class="card-body p-0">
            <BTable id="my-table" small striped hover responsive="md" primary-key="key" :no-local-sorting="false"
                    :items="transactions"
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
                <span class="fa fa-spinner fa-spin"></span>
              </template>
              <template #cell(type)="data">
                <span v-if="!data.item.dummy">
                  <span class="fas fa-long-arrow-alt-right" v-if="'deposit' === data.item.type.toLowerCase()"></span>
                  <span class="fas fa-long-arrow-alt-left" v-if="'withdrawal' === data.item.type.toLowerCase()"></span>
                  <span class="fas fa-arrows-alt-h" v-if="'transfer' === data.item.type.toLowerCase()"></span>
                </span>
              </template>
              <template #cell(description)="data">
                <span class="fa fa-spinner fa-spin" v-if="data.item.dummy"></span>
                <span v-if="!data.item.split">
                  <a :href="'./transactions/show/' + data.item.id" :title="data.value">
                  {{ data.item.description }}
                  </a>
                </span>
                <span v-if="data.item.split">
                  <!-- title first -->
                  <span class="fas fa-angle-right" @click="toggleCollapse(data.item.id)" style="cursor: pointer;"></span>
                  <a :href="'./transactions/show/' + data.item.id" :title="data.value">
                  {{ data.item.description }}
                  </a><br />
                  <span v-if="!data.item.collapsed">
                    <span v-for="(split, index) in data.item.splits" v-bind:key="index">
                      &nbsp; &nbsp; {{ split.description }}<br />
                    </span>
                  </span>
                </span>
              </template>
              <template #cell(amount)="data">
                <!-- row amount first (3x) -->
                <span :class="'text-success ' + (!data.item.collapsed ? 'font-weight-bold' : '')" v-if="'deposit' === data.item.type">
                  {{ Intl.NumberFormat(locale, {style: 'currency', currency: data.item.currency_code}).format(data.item.amount) }}
                </span>
                <span :class="'text-danger ' + (!data.item.collapsed ? 'font-weight-bold' : '')" v-if="'withdrawal' === data.item.type">
                  {{ Intl.NumberFormat(locale, {style: 'currency', currency: data.item.currency_code}).format(-data.item.amount) }}
                </span>
                <span :class="'text-muted ' + (!data.item.collapsed ? 'font-weight-bold' : '')" v-if="'transfer' === data.item.type.toLowerCase()">
                  {{ Intl.NumberFormat(locale, {style: 'currency', currency: data.item.currency_code}).format(data.item.amount) }}
                </span>
                <br />
                <!-- splits -->
                <span v-if="!data.item.collapsed">
                  <span v-for="(split, index) in data.item.splits" v-bind:key="index">
                    {{ Intl.NumberFormat(locale, {style: 'currency', currency: split.currency_code}).format(split.amount) }}<br />
                  </span>
                </span>
              </template>
              <template #cell(date)="data">
                {{ data.item.date_formatted }}
              </template>
              <template #cell(source_account)="data">
                <!-- extra break for splits -->
                <span v-if="true===data.item.split && !data.item.collapsed">
                  <br />
                </span>
                <em v-if="true===data.item.split && data.item.collapsed">
                  ...
                </em>

                <!-- loop all accounts, hidden if split -->
                <span v-for="(split, index) in data.item.splits" v-bind:key="index" v-if="false===data.item.split || (true===data.item.split && !data.item.collapsed)">
                  <a :href="'./accounts/show/' + split.source_id" :title="split.source_name">{{ split.source_name }}</a><br />
                </span>
              </template>
              <template #cell(destination_account)="data">
                <!-- extra break for splits -->
                <span v-if="true===data.item.split && !data.item.collapsed">
                  <br />
                </span>
                <em v-if="true===data.item.split && data.item.collapsed">
                  ...
                </em>

                <!-- loop all accounts, hidden if split -->
                <span v-for="(split, index) in data.item.splits" v-bind:key="index" v-if="false===data.item.split || (true===data.item.split && !data.item.collapsed)">
                  <a :href="'./accounts/show/' + split.destination_id" :title="split.destination_name">{{ split.destination_name }}</a><br />
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
                      <a class="dropdown-item" :href="'./transactions/edit/' + data.item.id"><span class="fa fas fa-pencil-alt"></span> {{
                          $t('firefly.edit')
                        }}</a>
                      <a class="dropdown-item" :href="'./transactions/delete/' + data.item.id"><span class="fa far fa-trash"></span> {{
                          $t('firefly.delete')
                        }}</a>
                    </div>
                  </div>
                </div>
              </template>
              <template #cell(category_name)="data">
                <!-- extra break for splits -->
                <span v-if="true===data.item.split && !data.item.collapsed">
                  <br />
                </span>
                <em v-if="true===data.item.split && data.item.collapsed">
                  ...
                </em>

                <!-- loop all categories, hidden if split -->
                <span v-for="(split, index) in data.item.splits" v-bind:key="index" v-if="false===data.item.split || (true===data.item.split && !data.item.collapsed)">
                  <a :href="'./categories/show/' + split.category_id" :title="split.category_name">{{ split.category_name }}</a><br />
                </span>
              </template>
            </BTable>

          </div>
          <div class="card-footer"> (button)
            <!--
            <a :href="'./transactions/create/' + type" class="btn btn-success"
               :title="$t('firefly.create_new_transaction')">{{ $t('firefly.create_new_transaction') }}</a>
               -->
          </div>
        </div>
      </div>
    </div>
    <div class="row">
      <div class="col-lg-8 col-md-6 col-sm-12 col-xs-12">
        <BPagination
            v-model="currentPage"
            :total-rows="total"
            :per-page="perPage"
            aria-controls="my-table"
        ></BPagination>
      </div>
      <div class="col-lg-4 col-md-6 col-sm-12 col-xs-12">
        <button @click="newCacheKey" class="btn btn-sm float-right btn-info"><span class="fas fa-sync"></span></button>
      </div>
    </div>
  </div>
</template>

<script>
import {mapGetters, mapMutations} from "vuex";
import {BPagination, BTable} from 'bootstrap-vue';
import format from "date-fns/format";

export default {
  name: "TransactionListLarge",
  components: {BPagination, BTable},
  data() {
    return {
      locale: 'en-US',
      fields: [],
      currentPage: 1,
      transactions: [],
      loading: true
    }
  },
  computed: {
    ...mapGetters('root', ['listPageSize', 'cacheKey']),
  },
  created() {
    this.locale = localStorage.locale ?? 'en-US';
    this.updateFieldList();
    //this.currentPage = this.page;
    this.parseTransactions();
  },
  watch: {
    currentPage: function (value) {
      // console.log('Watch currentPage go to ' + value);
      this.$emit('jump-page', {page: value});
    },
    entries: function (value) {
      console.log('detected new transactions!');
      this.parseTransactions();
    },
    value: function(value) {
      console.log('Watch value!');
    }
  },
  methods: {
    ...mapMutations('root', ['refreshCacheKey',]),
    parseTransactions: function () {
      this.transactions = [];
      // console.log('Start of parseTransactions. Count of entries is ' + this.entries.length + ' and page is ' + this.page);
      // console.log('Reported total is ' + this.total);
      if (0 === this.entries.length) {
        console.log('Will not render now');
        return;
      }
      console.log('Now have ' + this.transactions.length + ' transactions');
      for (let i = 0; i < this.total; i++) {
        this.transactions.push({dummy: true,type: 'x'});
        // console.log('Push dummy to index ' + i);
        // console.log('Now have ' + this.transactions.length + ' transactions');
      }
      // console.log('Generated ' + this.total + ' dummies');
      // console.log('Now have ' + this.transactions.length + ' transactions');
      let index = (this.page - 1) * this.perPage;
      // console.log('Start index is ' + index);
      for (let i in this.entries) {
        let transaction = this.entries[i];

        // build split
        this.transactions[index] = this.parseTransaction(transaction);
        // console.log('Push transaction to index ' + index);
        // console.log('Now have ' + this.transactions.length + ' transactions');
        index++;
      }
      // console.log('Added ' + this.entries.length + ' entries');
      // console.log('Now have ' + this.transactions.length + ' transactions');
      // console.log(this.transactions);


      this.loading = false;
    },
    newCacheKey: function () {
      this.refreshCacheKey();
      console.log('Cache key is now ' + this.cacheKey);
      this.$emit('refreshed-cache-key');
    },
    updateFieldList: function () {
      this.fields = [
        {key: 'type', label: ' ', sortable: false},
        {key: 'description', label: this.$t('list.description'), sortable: true},
        {key: 'amount', label: this.$t('list.amount'), sortable: true},
        {key: 'date', label: this.$t('list.date'), sortable: true},
        {key: 'source_account', label: this.$t('list.source_account'), sortable: true},
        {key: 'destination_account', label: this.$t('list.destination_account'), sortable: true},
        {key: 'category_name', label: this.$t('list.category'), sortable: true},
        {key: 'menu', label: ' ', sortable: false},
      ];
    },
    /**
     * Parse a single transaction.
     * @param transaction
     */
    parseTransaction: function (transaction) {
      let row = {};

      // default values:
      row.splits = [];
      row.key = transaction.id;
      row.id = transaction.id
      row.dummy = false;

      // pick this up from the first transaction
      let first = transaction.attributes.transactions[0];
      row.type = first.type;
      row.date = new Date(first.date);
      row.date_formatted = format(row.date, this.$t('config.month_and_day_fns'));
      row.description = first.description;
      row.collapsed = true;
      row.split = false;
      row.amount = 0;
      row.currency_code = first.currency_code;
      if (transaction.attributes.transactions.length > 1) {
        row.split = true;
        row.description = transaction.attributes.group_title;
      }

      // collapsed?
      if (typeof transaction.collapsed !== 'undefined') {
        row.collapsed = transaction.collapsed;
      }
      //console.log('is collapsed? ' + row.collapsed);

      // then loop each split
      for (let i in transaction.attributes.transactions) {
        if (transaction.attributes.transactions.hasOwnProperty(i)) {
          let info = transaction.attributes.transactions[i];
          let split = {};
          row.amount = row.amount + parseFloat(info.amount);
          split.type = info.type;
          split.description = info.description;
          split.amount = info.amount;
          split.currency_code = info.currency_code;
          split.source_name = info.source_name;
          split.source_id = info.source_id;
          split.destination_name = info.destination_name;
          split.destination_id = info.destination_id;
          split.category_id = info.category_id;
          split.category_name = info.category_name;
          split.split_index = i;
          row.splits.push(split);
        }
      }
      return row;
    },
    toggleCollapse: function (id) {
      let transaction = this.transactions.filter(transaction => transaction.id === id)[0];
      transaction.collapsed = !transaction.collapsed;
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

  props: {
    page: {
      type: Number
    },
    perPage: {
      type: Number,
      default: 1
    },
    sortDesc: {
      type: Boolean,
      default: true
    },
    total: {
      type: Number,
      default: 1
    },
    entries: {
      type: Array,
      default: function () {
        return [];
      }
    },
    accountId: {
      type: Number,
      default: function () {
        return 0;
      }
    },
  }
}
</script>

