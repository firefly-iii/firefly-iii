<!--
  - BudgetRow.vue
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
  <tr>
    <td style="width:25%;">
      <a :href="'./budgets/show/' + budgetLimit.budget_id">{{ budgetLimit.budget_name }}</a>
    </td>

    <td style="vertical-align: middle">
      <div class="progress progress active">
        <div class="progress-bar bg-success progress-bar-striped" role="progressbar"
             :aria-valuenow="budgetLimit.pctGreen" aria-valuemin="0" aria-valuemax="100" :style="'width: '+ budgetLimit.pctGreen + '%;'">
                      <span v-if="budgetLimit.pctGreen > 35">
                        Spent
                        {{ Intl.NumberFormat(locale, {style: 'currency', currency: budgetLimit.currency_code}).format(budgetLimit.spent) }}
                        of
                        {{ Intl.NumberFormat(locale, {style: 'currency', currency: budgetLimit.currency_code}).format(budgetLimit.amount) }}
                      </span>


        </div>

        <div class="progress-bar bg-warning progress-bar-striped" role="progressbar"
             :aria-valuenow="budgetLimit.pctOrange" aria-valuemin="0" aria-valuemax="100" :style="'width: '+ budgetLimit.pctOrange + '%;'">
                    <span v-if="budgetLimit.pctRed <= 50 && budgetLimit.pctOrange > 35">
                        Spent
                        {{ Intl.NumberFormat(locale, {style: 'currency', currency: budgetLimit.currency_code}).format(budgetLimit.spent) }}
                        of
                        {{ Intl.NumberFormat(locale, {style: 'currency', currency: budgetLimit.currency_code}).format(budgetLimit.amount) }}
                      </span>
        </div>

        <div class="progress-bar bg-danger progress-bar-striped" role="progressbar"
             :aria-valuenow="budgetLimit.pctRed" aria-valuemin="0" aria-valuemax="100" :style="'width: '+ budgetLimit.pctRed + '%;'">
                      <span v-if="budgetLimit.pctOrange <= 50 && budgetLimit.pctRed > 35">
                        Spent
                        {{ Intl.NumberFormat(locale, {style: 'currency', currency: budgetLimit.currency_code}).format(budgetLimit.spent) }}
                        of
                        {{ Intl.NumberFormat(locale, {style: 'currency', currency: budgetLimit.currency_code}).format(budgetLimit.amount) }}
                      </span>
        </div>
      </div>
      <small class="d-none d-lg-block">
        {{ new Intl.DateTimeFormat(locale, {year: 'numeric', month: 'long', day: 'numeric'}).format(budgetLimit.start) }}
        &rarr;
        {{ new Intl.DateTimeFormat(locale, {year: 'numeric', month: 'long', day: 'numeric'}).format(budgetLimit.end) }}
      </small>
    </td>

    <td style="width:10%;" class="align-middle d-none d-lg-table-cell">
      <span class="text-success" v-if="parseFloat(budgetLimit.amount) + parseFloat(budgetLimit.spent) > 0">
                    {{
          Intl.NumberFormat(locale, {
            style: 'currency',
            currency: budgetLimit.currency_code
          }).format(parseFloat(budgetLimit.amount) + parseFloat(budgetLimit.spent))
        }}
                  </span>
      <span class="text-muted" v-if="0.0 === parseFloat(budgetLimit.amount) + parseFloat(budgetLimit.spent)">
                    {{ Intl.NumberFormat(locale, {style: 'currency', currency: budgetLimit.currency_code}).format(0) }}
                  </span>
      <span class="text-danger" v-if="parseFloat(budgetLimit.amount) + parseFloat(budgetLimit.spent) < 0">
                    {{
          Intl.NumberFormat(locale, {
            style: 'currency',
            currency: budgetLimit.currency_code
          }).format(parseFloat(budgetLimit.amount) + parseFloat(budgetLimit.spent))
        }}
                  </span>
    </td>
  </tr>

</template>

<script>
export default {
  name: "BudgetLimitRow",
  props: {
    budgetLimit: {
      type: Object,
      default: {}
    },
    budget: {
      type: Object,
      default: {}
    }
  }
}
</script>

<style scoped>

</style>