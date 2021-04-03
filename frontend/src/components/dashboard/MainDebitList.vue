<!--
  - MainDebitList.vue
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
  <div class="card">
    <div class="card-header">
      <h3 class="card-title">{{ $t('firefly.expense_accounts') }}</h3>
    </div>
    <!-- body if loading -->
    <div v-if="loading && !error" class="card-body">
      <div class="text-center">
        <i class="fas fa-spinner fa-spin"></i>
      </div>
    </div>
    <!-- body if error -->
    <div v-if="error" class="card-body">
      <div class="text-center">
        <i class="fas fa-exclamation-triangle text-danger"></i>
      </div>
    </div>
    <!-- body if normal -->
    <div v-if="!loading && !error" class="card-body table-responsive p-0">
      <table class="table table-sm">
        <caption style="display:none;">{{ $t('firefly.expense_accounts') }}</caption>
        <thead>
        <tr>
          <th scope="col">{{ $t('firefly.category') }}</th>
          <th scope="col">{{ $t('firefly.spent') }}</th>
        </tr>
        </thead>
        <tbody>
        <tr v-for="entry in expenses">
          <td style="width:20%;"><a :href="'./accounts/show/' +  entry.id">{{ entry.name }}</a></td>
          <td class="align-middle">
            <div v-if="entry.pct > 0" class="progress">
              <div :aria-valuenow="entry.pct" :style="{ width: entry.pct  + '%'}" aria-valuemax="100"
                   aria-valuemin="0" class="progress-bar progress-bar-striped bg-danger"
                   role="progressbar">
                <span v-if="entry.pct > 20">
                  {{ Intl.NumberFormat(locale, {style: 'currency', currency: entry.currency_code}).format(entry.difference_float) }}
                </span>
              </div>
              <span v-if="entry.pct <= 20" style="line-height: 16px;">&nbsp;
              {{ Intl.NumberFormat(locale, {style: 'currency', currency: entry.currency_code}).format(entry.difference_float) }}
              </span>
            </div>
          </td>
        </tr>
        </tbody>
      </table>
    </div>
    <div class="card-footer">
      <a class="btn btn-default button-sm" href="./transactions/withdrawal"><i class="far fa-money-bill-alt"></i> {{ $t('firefly.go_to_withdrawals') }}</a>
    </div>
  </div>
</template>

<script>

import {createNamespacedHelpers} from "vuex";

const {mapState, mapGetters, mapActions, mapMutations} = createNamespacedHelpers('dashboard/index')


export default {
  name: "MainDebitList",
  data() {
    return {
      locale: 'en-US',
      expenses: [],
      min: 0,
      loading: true,
      error: false
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
    }
  },
  watch: {
    datesReady: function (value) {
      if (true === value) {
        this.getExpenses();
      }
    },
    start: function () {
      if (false === this.loading) {
        this.getExpenses();
      }
    },
    end: function () {
      if (false === this.loading) {
        this.getExpenses();
      }
    },
  },
  methods: {
    getExpenses() {
      this.loading = true;
      this.error = false;
      this.expenses = [];
      let startStr = this.start.toISOString().split('T')[0];
      let endStr = this.end.toISOString().split('T')[0];
      axios.get('./api/v1/insight/expense/expense?start=' + startStr + '&end=' + endStr)
          .then(response => {
            // do something with response.
            this.parseExpenses(response.data);
            this.loading = false
          }).catch(error => {
        this.error = true
      });
    },
    parseExpenses(data) {
      for (let mainKey in data) {
        if (data.hasOwnProperty(mainKey) && /^0$|^[1-9]\d*$/.test(mainKey) && mainKey <= 4294967294) {
          let current = data[mainKey];
          current.pct = 0;

          this.min = current.difference_float < this.min ? current.difference_float : this.min;
          this.expenses.push(current);
        }
      }

      if (0 === this.min) {
        this.min = -1;
      }
      // now sort + pct:
      for (let i in this.expenses) {
        if (this.expenses.hasOwnProperty(i)) {
          let current = this.expenses[i];
          current.pct = (current.difference_float*-1 / this.min*-1) * 100;
          this.expenses[i] = current;
        }
      }
      this.expenses.sort((a,b) => (a.pct > b.pct) ? -1 : ((b.pct > a.pct) ? 1 : 0));

    }
  }
}
</script>
