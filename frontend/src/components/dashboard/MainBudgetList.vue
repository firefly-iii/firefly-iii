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
    <div v-if="!loading" class="row">
      <div v-if="budgetLimits.daily.length > 0" class="col-xl-6 col-lg-12 col-md-12 col-sm-12 col-xs-12">
        <BudgetListGroup :budgetLimits=budgetLimits.daily :title="$t('firefly.daily_budgets')"
        />
      </div>
      <div v-if="budgetLimits.weekly.length > 0" class="col-xl-6 col-lg-12 col-md-12 col-sm-12 col-xs-12">
        <BudgetListGroup :budgetLimits=budgetLimits.weekly :title="$t('firefly.weekly_budgets')"
        />
      </div>
      <div v-if="budgetLimits.monthly.length > 0" class="col-xl-6 col-lg-12 col-md-12 col-sm-12 col-xs-12">
        <BudgetListGroup :budgetLimits=budgetLimits.monthly :title="$t('firefly.monthly_budgets')"
        />
      </div>
      <div v-if="budgetLimits.quarterly.length > 0" class="col-xl-6 col-lg-12 col-md-12 col-sm-12 col-xs-12">
        <BudgetListGroup :budgetLimits=budgetLimits.quarterly :title="$t('firefly.quarterly_budgets')"
        />
      </div>
      <div v-if="budgetLimits.half_year.length > 0" class="col-xl-6 col-lg-12 col-md-12 col-sm-12 col-xs-12">
        <BudgetListGroup :budgetLimits=budgetLimits.half_year :title="$t('firefly.half_year_budgets')"
        />
      </div>
      <div v-if="budgetLimits.yearly.length > 0" class="col-xl-6 col-lg-12 col-md-12 col-sm-12 col-xs-12">
        <BudgetListGroup :budgetLimits=budgetLimits.yearly :title="$t('firefly.yearly_budgets')"
        />
      </div>
      <div v-if="budgetLimits.other.length > 0 || rawBudgets.length > 0" class="col-xl-6 col-lg-12 col-md-12 col-sm-12 col-xs-12">
        <BudgetListGroup :budgetLimits=budgetLimits.other :budgets="rawBudgets" :title="$t('firefly.other_budgets')"
        />
      </div>
    </div>
    <div v-if="loading && !error" class="row">
      <div class="col">
        <div class="card">
          <div class="card-body">
            <div class="text-center">
              <i class="fas fa-spinner fa-spin"></i>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import BudgetListGroup from "./BudgetListGroup";
import {createNamespacedHelpers} from "vuex";

const {mapState, mapGetters, mapActions, mapMutations} = createNamespacedHelpers('dashboard/index')

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
      budgets: {}, // used to collect some meta data.
      rawBudgets: [],
      locale: 'en-US',
      ready: false,
      loading: true,
      error: false
    }
  },
  created() {
    this.ready = true;
    this.locale = localStorage.locale ?? 'en-US';
  },
  watch: {
    datesReady: function (value) {
      if (true === value) {
        this.getBudgets();
      }
    },
    start: function () {
      if (false === this.loading) {
        this.getBudgets();
      }
    },
    end: function () {
      if (false === this.loading) {
        this.getBudgets();
      }
    },
  },
  computed: {
    ...mapGetters(['start', 'end']),
    'datesReady': function () {
      return null !== this.start && null !== this.end && this.ready;
    }
  },
  methods:
      {
        getBudgets: function () {
          this.budgets = {};
          this.rawBudgets = [];
          this.budgetLimits = {
            daily: [],
            weekly: [],
            monthly: [],
            quarterly: [],
            half_year: [],
            yearly: [],
            other: [],
          };
          this.loading = true;
          let startStr = this.start.toISOString().split('T')[0];
          let endStr = this.end.toISOString().split('T')[0];
          axios.get('./api/v1/budgets?start=' + startStr + '&end=' + endStr)
              .then(response => {
                      this.parseBudgets(response.data);
                    }
              );
        },
        parseBudgets(data) {
          for (let i in data.data) {
            if (data.data.hasOwnProperty(i) && /^0$|^[1-9]\d*$/.test(i) && i <= 4294967294) {
              let current = data.data[i];
              if (false === current.attributes.active) {
                // skip inactive budgets
                continue;
              }
              for (let ii in current.attributes.spent) {
                if (current.attributes.spent.hasOwnProperty(ii) && /^0$|^[1-9]\d*$/.test(ii) && ii <= 4294967294) {
                  let spentData = current.attributes.spent[ii];
                  this.rawBudgets.push(
                      {
                        id: parseInt(current.id),
                        name: current.attributes.name,
                        currency_id: parseInt(spentData.currency_id),
                        currency_code: spentData.currency_code,
                        spent: spentData.sum
                      }
                  );
                  console.log('Added budget ' + current.attributes.name + ' (' + spentData.currency_code + ')');
                }
              }
            }
          }
          this.getBudgetLimits();
        },
        getBudgetLimits() {
          let startStr = this.start.toISOString().split('T')[0];
          let endStr = this.end.toISOString().split('T')[0];
          axios.get('./api/v1/budget-limits?start=' + startStr + '&end=' + endStr)
              .then(response => {
                      this.parseBudgetLimits(response.data);
                      this.loading = false;
                    }
              );
        },
        parseBudgetLimits(data) {
          // collect budget meta data.
          for (let i in data.included) {
            if (data.included.hasOwnProperty(i) && /^0$|^[1-9]\d*$/.test(i) && i <= 4294967294) {
              let current = data.included[i];
              let currentId = parseInt(current.id);
              this.budgets[currentId] =
                  {
                    id: currentId,
                    name: current.attributes.name,
                  };
              console.log('Collected meta data: budget #' + currentId + ' is named ' + current.attributes.name);
            }
          }

          for (let i in data.data) {
            if (data.data.hasOwnProperty(i) && /^0$|^[1-9]\d*$/.test(i) && i <= 4294967294) {
              let current = data.data[i];
              let currentId = parseInt(current.id);
              let budgetId = parseInt(current.attributes.budget_id);
              let currencyId = parseInt(current.attributes.currency_id);
              let spentFloat = parseFloat(current.attributes.spent);
              let spentFloatPos = parseFloat(current.attributes.spent) * -1;
              let amount = parseFloat(current.attributes.amount);
              let period = current.attributes.period ?? 'other';
              let pctGreen = 0;
              let pctOrange = 0;
              let pctRed = 0;
              //console.log('Collected "' + period + '" budget limit #' + currentId + ' (part of budget #' + budgetId + ')');
              //console.log('Spent ' + spentFloat + ' of ' + amount);

              // remove budget info from rawBudgets if it's there:
              this.filterBudgets(budgetId, currencyId);

              // spent within budget:
              if (0.0 !== spentFloat && spentFloatPos < amount) {
                pctGreen = (spentFloatPos / amount) * 100;
              }

              // spent over budget
              if (0.0 !== spentFloatPos && spentFloatPos > amount) {
                pctOrange = (spentFloatPos / amount) * 100;
                pctRed = 100 - pctOrange;
              }
              let obj = {
                id: currentId,
                amount: current.attributes.amount,
                budget_id: budgetId,
                budget_name: this.budgets[current.attributes.budget_id].name,
                currency_id: currencyId,
                currency_code: current.attributes.currency_code,
                period: current.attributes.period,
                start: new Date(current.attributes.start),
                end: new Date(current.attributes.end),
                spent: current.attributes.spent,
                pctGreen: pctGreen,
                pctOrange: pctOrange,
                pctRed: pctRed,
              };

              this.budgetLimits[period].push(obj);
            }
          }
        },

        filterBudgets(budgetId, currencyId) {
          for (let i in this.rawBudgets) {
            if (this.rawBudgets.hasOwnProperty(i) && /^0$|^[1-9]\d*$/.test(i) && i <= 4294967294) {
              if (this.rawBudgets[i].currency_id === currencyId && this.rawBudgets[i].id === budgetId) {
                console.log('Budget ' + this.rawBudgets[i].name + ' with currency ' + this.rawBudgets[i].currency_code + ' will be removed in favor of a budget limit.');
                this.rawBudgets.splice(parseInt(i), 1);
              }
            }
          }
        }
      }
}
</script>

<style scoped>

</style>