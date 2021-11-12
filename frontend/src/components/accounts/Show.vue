<!--
  - Show.vue
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
      <div class="col-lg-12 col-md-6 col-sm-12 col-xs-12">
        <!-- Custom Tabs -->
        <!--
        <div class="card">
          <div class="card-header d-flex p-0">
            <h3 class="card-title p-3">Tabs</h3>
            <ul class="nav nav-pills ml-auto p-2">
              <li class="nav-item"><a class="nav-link active" href="#main_chart" data-toggle="tab">Chart</a></li>
              <li class="nav-item"><a class="nav-link" href="#budgets" data-toggle="tab">Budgets</a></li>
              <li class="nav-item"><a class="nav-link" href="#categories" data-toggle="tab">Categories</a></li>
            </ul>
          </div>
          <div class="card-body">
            <div class="tab-content">
              <div class="tab-pane active" id="main_chart">
                1: main chart
              </div>
              <div class="tab-pane" id="budgets">
                2: tree map from/to budget
              </div>
              <div class="tab-pane" id="categories">
                2: tree map from/to cat
              </div>
            </div>
          </div>
        </div>
        -->
      </div>
    </div>

    <TransactionListLarge
        :entries="rawTransactions"
        :page="currentPage"
        ref="list"
        :total="total"
        :per-page="perPage"
        :sort-desc="sortDesc"
        v-on:jump-page="jumpToPage($event)"
        v-on:refreshed-cache-key="refreshedKey"
    />
    <div class="row">
      <div class="col-lg-12 col-md-6 col-sm-12 col-xs-12">
        <div class="card">
          <div class="card-header">
            <h3 class="card-title">
              Blocks
            </h3>
          </div>
          <div class="card-body">
            Blocks
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import TransactionListLarge from "../transactions/TransactionListLarge";
import format from "date-fns/format";
import {mapGetters} from "vuex";
import {configureAxios} from "../../shared/forageStore";

export default {
  name: "Show",
  computed: {
    ...mapGetters('root', ['listPageSize', 'cacheKey']),
    ...mapGetters('dashboard/index', ['start', 'end',]),
    'showReady': function () {
      return null !== this.start && null !== this.end && null !== this.listPageSize && this.ready;
    },
  },
  data() {
    return {
      accountId: 0,
      rawTransactions: [],
      ready: false,
      loading: false,
      total: 0,
      sortDesc: false,
      currentPage: 1,
      perPage: 51,
      locale: 'en-US',
      api: null,
      nameLoading: false
    }
  },
  created() {
    this.ready = true;
    let parts = window.location.pathname.split('/');
    this.accountId = parseInt(parts[parts.length - 1]);
    this.perPage = this.listPageSize ?? 51;

    let params = new URLSearchParams(window.location.search);
    this.currentPage = params.get('page') ? parseInt(params.get('page')) : 1;
    this.getTransactions();
    this.updatePageTitle();
  },
  components: {TransactionListLarge},
  methods: {
    updatePageTitle: function () {
      if (this.showReady && !this.nameLoading) {
        // update page title.
        this.nameLoading = true;
        configureAxios().then(async (api) => {
          let url = './api/v1/accounts/' + this.accountId;
          api.get(url)
              .then(response => {
                let start = new Intl.DateTimeFormat(this.locale, {year: 'numeric', month: 'long', day: 'numeric'}).format(this.start);
                let end = new Intl.DateTimeFormat(this.locale, {year: 'numeric', month: 'long', day: 'numeric'}).format(this.end);
                document.getElementById('page-subTitle').innerText = this.$t('firefly.journals_in_period_for_account_js', {
                  start: start,
                  end: end,
                  title: response.data.data.attributes.name
                });
              });
        });

      }
    },
    refreshedKey: function () {
      this.loading = false;
      this.getTransactions();
      this.updatePageTitle();
    },
    getTransactions: function () {
      if (this.showReady && !this.loading) {
        this.loading = true;
        configureAxios().then(async (api) => {
          // console.log('Now getTransactions() x Start');
          let startStr = format(this.start, 'y-MM-dd');
          let endStr = format(this.end, 'y-MM-dd');
          this.rawTransactions = [];

          let url = './api/v1/accounts/' + this.accountId + '/transactions?page=1&limit=' + this.perPage + '&start=' + startStr + '&end=' + endStr + '&cache=' + this.cacheKey;

          api.get(url)
              .then(response => {
                      // console.log('Now getTransactions() DONE!');
                      this.total = parseInt(response.data.meta.pagination.total);
                      this.rawTransactions = response.data.data;
                      this.loading = false;
                    }
              );
        });

      }
    },
    jumpToPage: function (event) {
      // console.log('noticed a change!');
      this.currentPage = event.page;
      this.downloadTransactionList(event.page);
    },
  },
  watch: {
    start: function () {
      this.getTransactions();
      this.updatePageTitle();
    },
    end: function () {
      this.getTransactions();
      this.updatePageTitle();
    },
  }

}
</script>

