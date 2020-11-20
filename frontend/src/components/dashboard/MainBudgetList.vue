<!--
  - MainBudgetList.vue
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
    <!-- loop budget things: -->
    <div class="row" v-for="budgetType in budgetList">
      <div class="col" v-if="budgetLimits[budgetType].length > 0">
        <div class="card">
          <div class="card-header">
            <h3 class="card-title">{{ budgetType }} budgets</h3>
          </div>
          <div class="card-body table-responsive p-0">
            <table class="table table-sm">
              <tbody>
              <tr v-for="budgetLimitKey in budgetLimits[budgetType]">
                <td style="width:25%;">
                  {{ budgets[budgetLimitKey.budget_id].name }}<br/>
                  <small>
                    {{ new Intl.DateTimeFormat(locale, {year: 'numeric', month: 'long', day: 'numeric'}).format(budgetLimitKey.start) }}
                    &rarr;
                    {{ new Intl.DateTimeFormat(locale, {year: 'numeric', month: 'long', day: 'numeric'}).format(budgetLimitKey.end) }}
                  </small>
                </td>
                <td style="vertical-align: middle">
                  <div class="progress progress active">
                    <!-- green bar -->
                    <div class="progress-bar bg-success progress-bar-striped" role="progressbar"
                         :aria-valuenow="budgetLimitKey.pctGreen" aria-valuemin="0" aria-valuemax="100" :style="'width: '+ budgetLimitKey.pctGreen + '%;'">
                      <span v-if="budgetLimitKey.pctGreen > 35">
                        Spent
                        {{ Intl.NumberFormat(locale, {style: 'currency', currency: budgetLimitKey.currency_code}).format(budgetLimitKey.spent) }}
                        of
                        {{ Intl.NumberFormat(locale, {style: 'currency', currency: budgetLimitKey.currency_code}).format(budgetLimitKey.amount) }}
                      </span>


                    </div>
                    <!-- orange bar -->
                    <div class="progress-bar bg-warning progress-bar-striped" role="progressbar"
                         :aria-valuenow="budgetLimitKey.pctOrange" aria-valuemin="0" aria-valuemax="100" :style="'width: '+ budgetLimitKey.pctOrange + '%;'">
                    <span v-if="budgetLimitKey.pctRed <= 50 && budgetLimitKey.pctOrange > 35">
                        Spent
                        {{ Intl.NumberFormat(locale, {style: 'currency', currency: budgetLimitKey.currency_code}).format(budgetLimitKey.spent) }}
                        of
                        {{ Intl.NumberFormat(locale, {style: 'currency', currency: budgetLimitKey.currency_code}).format(budgetLimitKey.amount) }}
                      </span>
                    </div>

                    <!-- red bar -->
                    <div class="progress-bar bg-danger progress-bar-striped" role="progressbar"
                         :aria-valuenow="budgetLimitKey.pctRed" aria-valuemin="0" aria-valuemax="100" :style="'width: '+ budgetLimitKey.pctRed + '%;'">
                      <span v-if="budgetLimitKey.pctOrange <= 50 && budgetLimitKey.pctRed > 35">
                        Spent
                        {{ Intl.NumberFormat(locale, {style: 'currency', currency: budgetLimitKey.currency_code}).format(budgetLimitKey.spent) }}
                        of
                        {{ Intl.NumberFormat(locale, {style: 'currency', currency: budgetLimitKey.currency_code}).format(budgetLimitKey.amount) }}
                      </span>
                    </div>
                  </div>
                </td>
                <td style="width:10%;" class="align-middle">
                  {{  }}
                  <span class="text-success" v-if="parseFloat(budgetLimitKey.amount) + parseFloat(budgetLimitKey.spent) > 0">
                    {{ Intl.NumberFormat(locale, {style: 'currency', currency: budgetLimitKey.currency_code}).format(parseFloat(budgetLimitKey.amount) + parseFloat(budgetLimitKey.spent)) }}
                  </span>
                  <span class="text-muted" v-if="0.0 === parseFloat(budgetLimitKey.amount) + parseFloat(budgetLimitKey.spent)">
                    {{ Intl.NumberFormat(locale, {style: 'currency', currency: budgetLimitKey.currency_code}).format(0) }}
                  </span>
                  <span class="text-danger" v-if="parseFloat(budgetLimitKey.amount) + parseFloat(budgetLimitKey.spent) < 0">
                    {{ Intl.NumberFormat(locale, {style: 'currency', currency: budgetLimitKey.currency_code}).format(parseFloat(budgetLimitKey.amount) + parseFloat(budgetLimitKey.spent)) }}
                  </span>
                </td>
              </tr>
              </tbody>
            </table>
          </div>
          <div class="card-footer">
            <a href="./budgets" class="btn btn-default button-sm"><i class="far fa-money-bill-alt"></i> {{ $t('firefly.go_to_budgets') }}</a>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: "MainBudgetList",
  data() {
    return {
      budgetList: ['daily', 'weekly', 'monthly', 'quarterly', 'half_year', 'yearly', 'other'],
      budgetLimits: {
        daily: [],
        weekly: [],
        monthly: [],
        quarterly: [],
        half_year: [],
        yearly: [],
        other: [],
      },
      budgets: {},
      locale: 'en-US',
    }
  },
  mounted() {
    this.getBudgets();
    this.locale = localStorage.locale ?? 'en-US';
  },
  methods:
      {
        getBudgets() {
          axios.get('./api/v1/budgets/limits?start=' + window.sessionStart + '&end=' + window.sessionEnd)
              .then(response => {
                      this.parseBudgets(response.data);
                    }
              );
        },
        parseBudgets(data) {
          // loop budgets (and do what?)
          for (let key in data.included) {
            if (data.included.hasOwnProperty(key) && /^0$|^[1-9]\d*$/.test(key) && key <= 4294967294) {
              let obj = {
                name: data.included[key].attributes.name,
                id: data.included[key].id,
              };
              this.budgets[data.included[key].id] = obj;
            }
          }

          // loop budget limits:
          for (let key in data.data) {
            if (data.data.hasOwnProperty(key) && /^0$|^[1-9]\d*$/.test(key) && key <= 4294967294) {
              let pctGreen = 0;
              let pctOrange = 0;
              let pctRed = 0;

              // spent within budget:
              if (0.0 !== parseFloat(data.data[key].attributes.spent) && (parseFloat(data.data[key].attributes.spent) * -1) < parseFloat(data.data[key].attributes.amount)) {
                pctGreen = (parseFloat(data.data[key].attributes.spent) * -1 / parseFloat(data.data[key].attributes.amount) * 100);
              }
              // spent over budget
              if (0.0 !== parseFloat(data.data[key].attributes.spent) && (parseFloat(data.data[key].attributes.spent) * -1) > parseFloat(data.data[key].attributes.amount)) {
                pctOrange = (parseFloat(data.data[key].attributes.amount) / parseFloat(data.data[key].attributes.spent) * -1) * 100;
                pctRed = 100 - pctOrange;
                //amount / spent
              }

              // if(pctGreen > 100) {
              //   pctGreen = 100;
              // }

              let obj = {
                id: data.data[key].id,
                amount: data.data[key].attributes.amount,
                budget_id: data.data[key].attributes.budget_id,
                currency_id: data.data[key].attributes.currency_id,
                currency_code: data.data[key].attributes.currency_code,
                period: data.data[key].attributes.period,
                start: new Date(data.data[key].attributes.start),
                end: new Date(data.data[key].attributes.end),
                spent: data.data[key].attributes.spent,
                pctGreen: pctGreen,
                pctOrange: pctOrange,
                pctRed: pctRed,
              };


              console.log(data.data[key]);

              let period = data.data[key].attributes.period ?? 'other';
              this.budgetLimits[period].push(obj);
            }
          }

        }
      }
}
</script>

<style scoped>

</style>