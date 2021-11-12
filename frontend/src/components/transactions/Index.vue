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
    <!--
    <div class="row">
      <div class="col">
        <div class="card">
          <div class="card-body">
            Treemap categories
          </div>
        </div>
      </div>
      <div class="col">
        <div class="card">
          <div class="card-body">
            Treemap accounts
          </div>
        </div>
      </div>
    </div>
    -->
    <!-- page is ignored for the time being -->
    <TransactionListLarge
        :entries="rawTransactions"
        :page="currentPage"
        :total="total"
        :per-page="perPage"
        :sort-desc="sortDesc"
        v-on:jump-page="jumpToPage($event)"
        v-on:refreshed-cache-key="refreshedKey"
    />
    <!--
    <div class="row">
      <div class="col-xl-2 col-lg-4 col-sm-6 col-xs-12" v-for="range in ranges">
        <div class="card">
          <div class="card-header">
            <h3 class="card-title">{{ formatDate(range.start, 'yyyy-LL') }}</h3>
          </div>
          <div class="card-body">
            <a :href="'./transactions/' + type + '/' + formatDate(range.start,'yyyy-LL-dd') + '/' + formatDate(range.end, 'yyyy-LL-dd')">Transactions</a>
          </div>
        </div>
      </div>

    </div>
    -->
  </div>
</template>

<script>

import {mapGetters, mapMutations} from "vuex";
import format from "date-fns/format";
import sub from "date-fns/sub";
import startOfMonth from "date-fns/startOfMonth";
import endOfMonth from "date-fns/endOfMonth";
import {configureAxios} from "../../shared/forageStore";
import TransactionListLarge from "./TransactionListLarge";

export default {
  name: "Index",
  components: {TransactionListLarge},
  data() {
    return {
      rawTransactions: [],
      type: 'all',
      downloaded: false,
      loading: false,
      ready: false,
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
      locale: 'en-US',
      ranges: [],
      urlStart: null,
      urlEnd: null,
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
    this.locale = localStorage.locale ?? 'en-US';
    let pathName = window.location.pathname;
    let parts = pathName.split('/');
    this.type = parts[parts.length - 1];
    this.perPage = this.listPageSize ?? 51;

    if (5 === parts.length) {
      this.urlStart = new Date(parts[3]);
      this.urlEnd = new Date(parts[4]);
      this.type = parts[parts.length - 3];
    }

    let params = new URLSearchParams(window.location.search);
    this.currentPage = params.get('page') ? parseInt(params.get('page')) : 1;
    this.ready = true;
  },
  methods: {
    ...mapMutations('root', ['refreshCacheKey',]),
    refreshedKey: function () {
      this.downloaded = false;
      this.rawTransactions = [];
      this.getTransactionList();
    },
    jumpToPage: function (event) {
      // console.log('noticed a change!');
      this.currentPage = event.page;
      this.downloadTransactionList(event.page);
    },
    getTransactionList: function () {
      if (this.indexReady && !this.loading && !this.downloaded) {
        this.loading = true;
        this.perPage = this.listPageSize ?? 51;
        this.rawTransactions = [];
        this.downloadTransactionList(this.currentPage);
        this.calculateDateRanges();
      }
    },
    calculateDateRanges: function () {
      let yearAgo = sub(this.start, {years: 1});
      let currentDate = this.start;

      while (currentDate > yearAgo) {
        let st = startOfMonth(currentDate);
        let en = endOfMonth(currentDate);

        this.ranges.push({start: st, end: en});

        currentDate = sub(currentDate, {months: 1});
      }
    },
    formatDate: function (date, frm) {
      return format(date, frm);
    },
    downloadTransactionList: function (page) {
      configureAxios().then(async (api) => {
        let startStr = format(this.start, 'y-MM-dd');
        let endStr = format(this.end, 'y-MM-dd');

        if (null !== this.urlEnd && null !== this.urlStart) {
          startStr = format(this.urlStart, 'y-MM-dd');
          endStr = format(this.urlEnd, 'y-MM-dd');
        }

        let url = './api/v1/transactions?type=' + this.type + '&page=' + page + "&start=" + startStr + "&end=" + endStr + '&cache=' + this.cacheKey;
        api.get(url)
            .then(response => {
                    this.total = parseInt(response.data.meta.pagination.total);
                    this.rawTransactions = response.data.data;
                    this.loading = false;
                  }
            );
      });
    },


  },
}
</script>