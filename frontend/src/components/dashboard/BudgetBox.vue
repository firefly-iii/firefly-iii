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
        <div v-for="budget in budgets" :key="budget.id">
          <div class="row">
            <div class="col">
              <router-link :to="{ name: 'budgets.show', params: {id: budget.id} }">
                {{ budget.name }}
              </router-link>
            </div>
          </div>
          <div v-for="limit in budget.limits">
            <div class="row">
              <div class="col">
                <small>{{ limit.amount }}</small><br>
                {{ limit.start }}<br>
                {{ limit.end }}
              </div>
              <div class="col">
                I am bar
              </div>
            </div>
          </div>
        </div>

        <div class="row">
          <div class="col">
            I am no budget<br/>
          </div>
        </div>
      </q-card-section>

    </q-card>
  </div>
</template>

<script>
import {useFireflyIIIStore} from "../../stores/fireflyiii";
import List from '../../api/v2/budgets/list';
import ListLimit from '../../api/v2/budget-limits/list';

export default {
  name: "BudgetBox",
  data() {
    return {
      budgets: [],
      locale: 'en-US',
      page: 1,
      loadingBudgets: false
    }
  },
  mounted() {
    this.store = useFireflyIIIStore();
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
    loadBox: function () {
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
      // todo go to next page as well.
    },
    parseBudgets: function (data) {
      for (let i in data) {
        if (data.hasOwnProperty(i)) {
          const current = data[i];
          this.budgets.push(
            {
              id: parseInt(current.id),
              name: current.attributes.name,
              limits: [],
            }
          );
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
          });
        }
      }
      console.log('Processing...');
    },
    parseBudgetLimits: function (data, budget) {
      console.log('Parse for ' + budget.name);
      for(let i in data) {
        if(data.hasOwnProperty(i)) {
          const current = data[i];
          budget.limits.push(
            {
              amount: current.attributes.amount,
              start: new Date(current.attributes.start),
              end: new Date(current.attributes.end),
            }
          );
          console.log('A ' +  new Date(current.attributes.start));
          console.log('B ' + this.store.getRange.start);
        }
      }
    }
  }
}
</script>

<style scoped>

</style>
