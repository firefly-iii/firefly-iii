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
    <!-- daily budgets (will be the exception, I expect) -->
    <div class="row">
      <div class="col-xl-6 col-lg-12 col-md-12 col-sm-12 col-xs-12" v-if="budgetLimits.daily.length > 0">
        <BudgetListGroup :title="$t('firefly.daily_budgets')" :budgetLimits=budgetLimits.daily
        />
      </div>
      <div class="col-xl-6 col-lg-12 col-md-12 col-sm-12 col-xs-12" v-if="budgetLimits.weekly.length > 0">
        <BudgetListGroup :title="$t('firefly.weekly_budgets')" :budgetLimits=budgetLimits.weekly
        />
      </div>
      <div class="col-xl-6 col-lg-12 col-md-12 col-sm-12 col-xs-12" v-if="budgetLimits.monthly.length > 0">
        <BudgetListGroup :title="$t('firefly.monthly_budgets')" :budgetLimits=budgetLimits.monthly
        />
      </div>
      <div class="col-xl-6 col-lg-12 col-md-12 col-sm-12 col-xs-12" v-if="budgetLimits.quarterly.length > 0">
        <BudgetListGroup :title="$t('firefly.quarterly_budgets')" :budgetLimits=budgetLimits.quarterly
        />
      </div>
      <div class="col-xl-6 col-lg-12 col-md-12 col-sm-12 col-xs-12" v-if="budgetLimits.half_year.length > 0">
        <BudgetListGroup :title="$t('firefly.half_year_budgets')" :budgetLimits=budgetLimits.half_year
        />
      </div>
      <div class="col-xl-6 col-lg-12 col-md-12 col-sm-12 col-xs-12" v-if="budgetLimits.yearly.length > 0">
        <BudgetListGroup :title="$t('firefly.yearly_budgets')" :budgetLimits=budgetLimits.yearly
        />
      </div>
      <div class="col-xl-6 col-lg-12 col-md-12 col-sm-12 col-xs-12" v-if="budgetLimits.other.length > 0 || rawBudgets.length > 0">
        <BudgetListGroup :title="$t('firefly.other_budgets')" :budgetLimits=budgetLimits.other :budgets="rawBudgets"
        />
      </div>
    </div>

  </div>
</template>

<script>
import BudgetListGroup from "./BudgetListGroup";

export default {
  name: "MainBudgetList",
  components: {BudgetListGroup},
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
      rawBudgets: [],
      locale: 'en-US',
    }
  },
  created() {
    this.locale = localStorage.locale ?? 'en-US';
    this.collectData();
  },
  methods:
      {
        collectData() {
          this.getBudgets();
        },
        getBudgets() {
          axios.get('./api/v1/budgets?start=' + window.sessionStart + '&end=' + window.sessionEnd)
              .then(response => {
                      this.parseBudgets(response.data);
                    }
              );
        },
        parseBudgets(data) {
          for (let key in data.data) {
            if (data.data.hasOwnProperty(key) && /^0$|^[1-9]\d*$/.test(key) && key <= 4294967294) {
              let current = data.data[key];
              for (let subKey in current.attributes.spent) {
                if (current.attributes.spent.hasOwnProperty(subKey) && /^0$|^[1-9]\d*$/.test(subKey) && subKey <= 4294967294) {
                  let spentData = current.attributes.spent[subKey];
                  this.rawBudgets.push(
                      {
                        id: parseInt(current.id),
                        name: current.attributes.name,
                        currency_id: parseInt(spentData.currency_id),
                        currency_code: spentData.currency_code,
                        spent: spentData.sum
                      }
                  );
                }
              }

            }
          }
          this.getBudgetLimits();
        },


        getBudgetLimits() {
          axios.get('./api/v1/budgets/limits?start=' + window.sessionStart + '&end=' + window.sessionEnd)
              .then(response => {
                      this.parseBudgetLimits(response.data);
                    }
              );
        },
        parseBudgetLimits(data) {
          for (let key in data.included) {
            if (data.included.hasOwnProperty(key) && /^0$|^[1-9]\d*$/.test(key) && key <= 4294967294) {
              this.budgets[data.included[key].id] =
                  {
                    id: data.included[key].id,
                    name: data.included[key].attributes.name,
                  };
            }
          }

          for (let key in data.data) {
            if (data.data.hasOwnProperty(key) && /^0$|^[1-9]\d*$/.test(key) && key <= 4294967294) {
              let pctGreen = 0;
              let pctOrange = 0;
              let pctRed = 0;

              // remove budget info from rawBudgets if it's there:
              this.filterBudgets(data.data[key].attributes.budget_id, data.data[key].attributes.currency_id);

              // spent within budget:
              if (0.0 !== parseFloat(data.data[key].attributes.spent) && (parseFloat(data.data[key].attributes.spent) * -1) < parseFloat(data.data[key].attributes.amount)) {
                pctGreen = (parseFloat(data.data[key].attributes.spent) * -1 / parseFloat(data.data[key].attributes.amount) * 100);
              }

              // spent over budget
              if (0.0 !== parseFloat(data.data[key].attributes.spent) && (parseFloat(data.data[key].attributes.spent) * -1) > parseFloat(data.data[key].attributes.amount)) {
                pctOrange = (parseFloat(data.data[key].attributes.amount) / parseFloat(data.data[key].attributes.spent) * -1) * 100;
                pctRed = 100 - pctOrange;
              }
              let obj = {
                id: data.data[key].id,
                amount: data.data[key].attributes.amount,
                budget_id: data.data[key].attributes.budget_id,
                budget_name: this.budgets[data.data[key].attributes.budget_id].name,
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

              let period = data.data[key].attributes.period ?? 'other';
              this.budgetLimits[period].push(obj);

            }
          }


          // // loop budgets (and do what?)
          // for (let key in data.included) {
          //   if (data.included.hasOwnProperty(key) && /^0$|^[1-9]\d*$/.test(key) && key <= 4294967294) {
          //     let obj = {
          //       name: data.included[key].attributes.name,
          //       id: data.included[key].id,
          //     };
          //     this.budgets[data.included[key].id] = obj;
          //   }
          // }

          // loop budget limits:
          // for (let key in data.data) {
          //   if (data.data.hasOwnProperty(key) && /^0$|^[1-9]\d*$/.test(key) && key <= 4294967294) {
          //     let pctGreen = 0;
          //     let pctOrange = 0;
          //     let pctRed = 0;
          //

          //
          //     let obj = {
          //       id: data.data[key].id,
          //       amount: data.data[key].attributes.amount,
          //       budget_id: data.data[key].attributes.budget_id,
          //       currency_id: data.data[key].attributes.currency_id,
          //       currency_code: data.data[key].attributes.currency_code,
          //       period: data.data[key].attributes.period,
          //       start: new Date(data.data[key].attributes.start),
          //       end: new Date(data.data[key].attributes.end),
          //       spent: data.data[key].attributes.spent,
          //       pctGreen: pctGreen,
          //       pctOrange: pctOrange,
          //       pctRed: pctRed,
          //     };
          //
          //
          //
          //     let period = data.data[key].attributes.period ?? 'other';
          //     this.budgetLimits[period].push(obj);
          //   }
          // }

        },
        filterBudgets(budgetId, currencyId) {
          for (let key in this.rawBudgets) {
            if (this.rawBudgets.hasOwnProperty(key) && /^0$|^[1-9]\d*$/.test(key) && key <= 4294967294) {
              if (this.rawBudgets[key].currency_id === currencyId && this.rawBudgets[key].id === budgetId) {
                this.rawBudgets.splice(key, 1);
              }
            }
          }
        }
      }
}
</script>

<style scoped>

</style>