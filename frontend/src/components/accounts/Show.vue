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
        <div class="card">
          <div class="card-header d-flex p-0">
            <h3 class="card-title p-3">Tabs</h3>
            <ul class="nav nav-pills ml-auto p-2">
              <li class="nav-item"><a class="nav-link active" href="#main_chart" data-toggle="tab">Chart</a></li>
              <li class="nav-item"><a class="nav-link" href="#budgets" data-toggle="tab">Budgets</a></li>
              <li class="nav-item"><a class="nav-link" href="#categories" data-toggle="tab">Categories</a></li>
            </ul>
          </div><!-- /.card-header -->
          <div class="card-body">
            <div class="tab-content">
              <div class="tab-pane active" id="main_chart">
                1: main chart
              </div>
              <!-- /.tab-pane -->
              <div class="tab-pane" id="budgets">
                2: tree map from/to budget
              </div>
              <!-- /.tab-pane -->
              <div class="tab-pane" id="categories">
                2: tree map from/to cat
              </div>
              <!-- /.tab-pane -->
            </div>
            <!-- /.tab-content -->
          </div><!-- /.card-body -->
        </div>
        <!-- ./card -->
      </div>
    </div>
    <div class="row">
      <div class="col-lg-12 col-md-6 col-sm-12 col-xs-12">
        <div class="card">
          <div class="card-header">
            <h3 class="card-title">
              Title
            </h3>
          </div>
          <div class="card-body">
            <TransactionListLarge :account_id=accountId :transactions=transactions>

            </TransactionListLarge>
          </div>
        </div>
      </div>
    </div>
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
      transactions: [],
      ready: false,
      currentPage: 1,
      perPage: 51,
      locale: 'en-US'
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
  },
  components: {TransactionListLarge},
  methods: {
    getTransactions: function () {
      console.log('goooooo');
      if (this.showReady) {
        let startStr = format(this.start, 'y-MM-dd');
        let endStr = format(this.end, 'y-MM-dd');
        axios.get('./api/v1/accounts/' + this.accountId + '/transactions?page=1&limit=' + this.perPage + '&start=' + startStr + '&end=' + endStr)
            .then(response => {
                    this.transactions = response.data.data;
                    //this.loading = false;
                    //this.error = false;
                  }
            );
      }
    }
  },
  watch: {
    start: function () {
      this.getTransactions();
    },
    end: function () {
      this.getTransactions();
    },
  }

}
</script>

