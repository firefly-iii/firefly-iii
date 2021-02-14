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
    <div class="card-body" v-if="loading && !error">
      <div class="text-center">
        <i class="fas fa-spinner fa-spin"></i>
      </div>
    </div>
    <!-- body if error -->
    <div class="card-body" v-if="error">
      <div class="text-center">
        <i class="fas fa-exclamation-triangle text-danger"></i>
      </div>
    </div>
    <!-- body if normal -->
    <div class="card-body table-responsive p-0" v-if="!loading && !error">
      <table class="table table-sm">
        <tbody>
        <tr v-for="entry in expenses">
          <td style="width:20%;"><a :href="'./accounts/show/' +  entry.id">{{ entry.name }}</a></td>
          <td class="align-middle">
            <div class="progress" v-if="entry.pct > 0">
              <div class="progress-bar progress-bar-striped bg-danger" role="progressbar" :aria-valuenow="entry.pct"
                   :style="{ width: entry.pct  + '%'}" aria-valuemin="0"
                   aria-valuemax="100">
                <span v-if="entry.pct > 20">
                  {{ Intl.NumberFormat(locale, {style: 'currency', currency: entry.currency_code}).format(entry.difference_float) }}
                </span>
              </div>
              <span v-if="entry.pct <= 20">&nbsp;
              {{ Intl.NumberFormat(locale, {style: 'currency', currency: entry.currency_code}).format(entry.difference_float) }}
              </span>
            </div>
          </td>
        </tr>
        </tbody>
      </table>
    </div>
    <div class="card-footer">
      <a href="./transactions/withdrawal" class="btn btn-default button-sm"><i class="far fa-money-bill-alt"></i> {{ $t('firefly.go_to_withdrawals') }}</a>
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
      max: 0,
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
    }
  },
  methods: {
    getExpenses() {
      let startStr = this.start.toISOString().split('T')[0];
      let endStr = this.end.toISOString().split('T')[0];
      axios.get('./api/v1/insight/expense/date/basic?start=' + startStr + '&end=' + endStr)
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
          // contains currency info and entries.
          let current = data[mainKey];
          if (0 === parseInt(mainKey)) {
            this.max = data[mainKey].difference_float;
            current.pct = 100;
          }
          if (0 !== parseInt(mainKey)) {
            // calc percentage:
            current.pct = (data[mainKey].difference_float / this.max) * 100;
          }
          this.expenses.push(current);

        }
      }
    }
  }
}
</script>
