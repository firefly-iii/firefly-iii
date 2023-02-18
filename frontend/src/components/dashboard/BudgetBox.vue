<!--
  - BudgetBox.vue
  - Copyright (c) 2022 james@firefly-iii.org
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

<!--
TODO needs padding
TODO needs better (hover) color.
-->

<template>
  <div class="q-mt-sm q-mr-sm">
    <q-card bordered>
      <q-item>
        <q-item-section>
          <q-item-label><strong>
            Budgets
          </strong></q-item-label>
        </q-item-section>
      </q-item>
      <q-separator/>
      <q-card-section>
        <div v-for="(budget, index) in budgets" :key="budget.id">
          <div :class="'row bg-blue-1'">
            <div class="col">
              <router-link :to="{ name: 'budgets.show', params: {id: budget.id} }">
                {{ budget.name }}
              </router-link>
            </div>
          </div>
          <div v-for="(limit, ii) in budget.limits">
            <div class="row">
              <div class="col">
                <small>
                  <span v-if="parseFloat(limit.amount) + parseFloat(limit.sum) > 0 || 0===parseFloat(limit.amount)">
                    Spent {{ formatAmount(limit.currency_code, limit.sum) }}
                    <span v-if="0 !== parseFloat(limit.amount)">
                    from {{ formatAmount(limit.currency_code, limit.amount) }}
                    </span>
                    <span v-if="null !== limit.start && null !== limit.end">
                      between
                      {{ formatDate(limit.start) }} -
                      {{ formatDate(limit.end) }}
                  </span>
                  </span>
                  <span v-if="parseFloat(limit.amount) + parseFloat(limit.sum) < 0 && 0 !== parseFloat(limit.amount)">
                    Overspent {{ formatAmount(limit.currency_code, (parseFloat(limit.amount) + parseFloat(limit.sum))*-1) }}
                    <span v-if="0 !== parseFloat(limit.amount)">
                    on {{ formatAmount(limit.currency_code, limit.amount) }}
                    </span>
                    <span v-if="null !== limit.start && null !== limit.end">
                      between
                      {{ formatDate(limit.start) }} -
                      {{ formatDate(limit.end) }}
                  </span>
                  </span>
                </small>

              </div>
              <div class="col">
                <q-linear-progress :indeterminate="budget.indeterminate" :value="limit.percentage" class="q-mt-md"/>
              </div>
            </div>
          </div>
        </div>

        <div class="row">
          <div class="col">
            No budget TODO<br/>
          </div>
        </div>
      </q-card-section>

    </q-card>
  </div>
</template>

<script>
import {useFireflyIIIStore} from "../../stores/fireflyiii";
import List from '../../api/v2/budgets/list';
import Get from '../../api/v2/budgets/get';
import ListLimit from '../../api/v2/budget-limits/list';
import format from "date-fns/format";

export default {
  name: "BudgetBox",
  data() {
    return {
      altClassBudget: 'bg-blue-1',
      altClassBl: 'bg-red-2',
      budgets: [],
      locale: 'en-US',
      page: 1,
      loadingBudgets: false,
      dateFormat: '',
    }
  },
  mounted() {
    this.store = useFireflyIIIStore();
    this.dateFormat = this.$t('config.month_and_day_fns');
    this.store.$onAction(
      ({name, store, args, after, onError,}) => {
        after((result) => {
          if (name === 'setRange') {
            this.locale = this.store.getLocale;
            this.loadBox();
          }
        })
      }
    )
    if (null !== this.store.getRange.start && null !== this.store.getRange.end) {
      this.loadBox();
    }
  },
  methods: {
    formatDate: function (date) {
      return format(new Date(date), this.$t('config.month_and_day_fns'));
    },
    formatAmount: function (currencyCode, amount) {
      return Intl.NumberFormat('en-US', {style: 'currency', currency: currencyCode}).format(amount);
    },
    loadBox: function () {
      console.log('loadBox');
      this.loadingBudgets = true;
      (new List).list(this.page).then((data) => {
        this.parseBudgets(data.data.data);
        if (data.data.meta.pagination.current_page < data.data.meta.pagination.total_pages) {
          this.page = data.data.meta.pagination.current_page + 1;
          this.loadBox();
          return;
        }
        this.loadingBudgets = false;
        this.processBudgets();
      });
      // load no-budget info.
      // todo go to next page as well.
    },
    parseBudgets: function (data) {
      console.log('parseBudgets');
      for (let i in data) {
        if (data.hasOwnProperty(i)) {
          const current = data[i];
          let entry = {
            id: parseInt(current.id),
            name: current.attributes.name,
            indeterminate: true,
            spent: [],
            limits: [],
          };
          this.budgets.push(
            entry
          );
        }
      }
    },
    loadSpentInfo: function (id) {
      (new Get).spent(id, this.store.getRange.start, this.store.getRange.end).then((response) => {
        this.parseSpentInfo(response.data, id);
      });
    },
    parseSpentInfo: function (data, id) {
      // todo parse info and put in array.
      for (let i in this.budgets) {
        if (this.budgets.hasOwnProperty(i)) {
          let budget = this.budgets[i];
          if (budget.id === id) {
            for (let ii in budget.limits) {
              if (budget.limits.hasOwnProperty(ii)) {
                let limit = budget.limits[ii];
                for (let iii in data) {
                  if (data.hasOwnProperty(iii)) {
                    let spent = data[iii];
                    if (spent.code === limit.currency_code) {
                      limit.sum = spent.sum;
                      let pct = (spent.sum * -1) / limit.amount;
                      limit.percentage = Math.min(Math.max(0, pct), 1);
                      budget.indeterminate = false;
                    }
                  }
                }
              }
            }
            let processed= false;
            for (let i in data) {
              let found = false;
              if (data.hasOwnProperty(i)) {
                let spent = data[i];
                for (let ii in budget.limits) {
                  if (budget.limits.hasOwnProperty(ii)) {
                    let limit = budget.limits[ii];
                    if (spent.code === limit.currency_code) {
                      found = true;
                    }
                  }
                }
                if (!found) {
                  processed = true;
                  budget.indeterminate = false;
                  console.log();
                  budget.limits.push(
                    {
                      id: 0,
                      sum: spent.sum,
                      amount: 0,
                      currency_code: spent.code,
                      start: null,
                      end: null,
                      // TODO calculate percentage from spent.
                      percentage: 0,
                      overspent: false,
                    }
                  );
                }
              }
            }
            if(!processed) {
              // limit but no expenses
              budget.indeterminate = false;
            }

          }
        }
      }
    },
    processBudgets: function () {
      for (let i in this.budgets) {
        if (this.budgets.hasOwnProperty(i)) {
          const current = this.budgets[i];
          // get budget limits in current view range.

          // todo must also be paginated because you never know
          (new ListLimit).list(current.id, this.store.getRange.start, this.store.getRange.end, 1).then((data) => {
            this.parseBudgetLimits(data.data.data, current);
            this.loadSpentInfo(current.id);
          });
        }
      }
      console.log('Processing...');
    },
    parseBudgetLimits: function (data, budget) {
      //console.log('Parse for ' + budget.name);
      for (let i in data) {
        if (data.hasOwnProperty(i)) {
          const current = data[i];
          budget.limits.push(
            {
              id: parseInt(current.id),
              amount: current.attributes.amount,
              currency_code: current.attributes.currency_code,
              start: new Date(current.attributes.start),
              end: new Date(current.attributes.end),
              percentage: 0,
              sum: 0,
              overspent: false,
            }
          );
          //console.log(current);
          //console.log('A ' + new Date(current.attributes.start));
          //console.log('B ' + this.store.getRange.start);
        }
      }
    }
  }
}
</script>

<style scoped>

</style>
