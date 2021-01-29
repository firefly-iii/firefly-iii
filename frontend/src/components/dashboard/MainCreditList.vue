<!--
  - MainCreditList.vue
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
      <h3 class="card-title">{{ $t('firefly.revenue_accounts') }}</h3>
    </div>
    <div class="card-body table-responsive p-0">
      <table class="table table-sm">
        <tbody>
        <tr v-for="entry in income">
          <td style="width:20%;"><a :href="'./accounts/show/' +  entry.id">{{ entry.name }}</a></td>
          <td class="align-middle">
            <div class="progress" v-if="entry.pct > 0">
              <div class="progress-bar progress-bar-striped bg-success" role="progressbar" :aria-valuenow="entry.pct"
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
      <a href="./transactions/deposit" class="btn btn-default button-sm"><i class="far fa-money-bill-alt"></i> {{ $t('firefly.go_to_deposits') }}</a>
    </div>
  </div>
</template>

<script>
export default {
  name: "MainCreditList",
  data() {
    return {
      locale: 'en-US',
      income: [],
      max: 0
    }
  },
  created() {
    this.locale = localStorage.locale ?? 'en-US';
    this.getExpenses();
  },
  methods: {
    getExpenses() {
      axios.get('./api/v1/insight/income/date/basic?start=' + window.sessionStart + '&end=' + window.sessionEnd)
          .then(response => {
            // do something with response.
            this.parseExpenses(response.data);
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
          if(0 !== parseInt(mainKey)) {
            // calc percentage:
            current.pct = (data[mainKey].difference_float / this.max) * 100;
          }
          this.income.push(current);

        }
      }
    }
  }
}
</script>
