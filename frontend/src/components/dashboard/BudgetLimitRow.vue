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
        <div :aria-valuenow="budgetLimit.pctGreen" :style="'width: '+ budgetLimit.pctGreen + '%;'"
             aria-valuemax="100" aria-valuemin="0" class="progress-bar bg-success progress-bar-striped" role="progressbar">
                      <span v-if="budgetLimit.pctGreen > 35">
                        {{ $t('firefly.spent_x_of_y', {amount: Intl.NumberFormat(locale, {style: 'currency', currency: budgetLimit.currency_code}).format(budgetLimit.spent), total: Intl.NumberFormat(locale, {style: 'currency', currency: budgetLimit.currency_code}).format(budgetLimit.amount)}) }}
                        <!--  -->
                      </span>


        </div>

        <div :aria-valuenow="budgetLimit.pctOrange" :style="'width: '+ budgetLimit.pctOrange + '%;'"
             aria-valuemax="100" aria-valuemin="0" class="progress-bar bg-warning progress-bar-striped" role="progressbar">
                    <span v-if="budgetLimit.pctRed <= 50 && budgetLimit.pctOrange > 35">
                      {{ $t('firefly.spent_x_of_y', {amount: Intl.NumberFormat(locale, {style: 'currency', currency: budgetLimit.currency_code}).format(budgetLimit.spent), total: Intl.NumberFormat(locale, {style: 'currency', currency: budgetLimit.currency_code}).format(budgetLimit.amount)}) }}
                      </span>
        </div>

        <div :aria-valuenow="budgetLimit.pctRed" :style="'width: '+ budgetLimit.pctRed + '%;'"
             aria-valuemax="100" aria-valuemin="0" class="progress-bar bg-danger progress-bar-striped" role="progressbar">
                      <span v-if="budgetLimit.pctOrange <= 50 && budgetLimit.pctRed > 35" class="text-muted">
                        {{ $t('firefly.spent_x_of_y', {amount: Intl.NumberFormat(locale, {style: 'currency', currency: budgetLimit.currency_code}).format(budgetLimit.spent), total: Intl.NumberFormat(locale, {style: 'currency', currency: budgetLimit.currency_code}).format(budgetLimit.amount)}) }}
                      </span>
        </div>

        <!-- amount if bar is very small -->
        <span v-if="budgetLimit.pctGreen <= 35 && 0 === budgetLimit.pctOrange && 0 === budgetLimit.pctRed && 0 !== budgetLimit.pctGreen" style="line-height: 16px;">
          &nbsp; {{ $t('firefly.spent_x_of_y', {amount: Intl.NumberFormat(locale, {style: 'currency', currency: budgetLimit.currency_code}).format(budgetLimit.spent), total: Intl.NumberFormat(locale, {style: 'currency', currency: budgetLimit.currency_code}).format(budgetLimit.amount)}) }}
                      </span>

      </div>
      <small class="d-none d-lg-block">
        {{ new Intl.DateTimeFormat(locale, {year: 'numeric', month: 'long', day: 'numeric'}).format(budgetLimit.start) }}
        &rarr;
        {{ new Intl.DateTimeFormat(locale, {year: 'numeric', month: 'long', day: 'numeric'}).format(budgetLimit.end) }}
      </small>
    </td>

    <td class="align-middle d-none d-lg-table-cell" style="width:10%;">
      <span v-if="parseFloat(budgetLimit.amount) + parseFloat(budgetLimit.spent) > 0" class="text-success">
                    {{
          Intl.NumberFormat(locale, {
            style: 'currency',
            currency: budgetLimit.currency_code
          }).format(parseFloat(budgetLimit.amount) + parseFloat(budgetLimit.spent))
        }}
                  </span>
      <span v-if="0.0 === parseFloat(budgetLimit.amount) + parseFloat(budgetLimit.spent)" class="text-muted">
                    {{ Intl.NumberFormat(locale, {style: 'currency', currency: budgetLimit.currency_code}).format(0) }}
                  </span>
      <span v-if="parseFloat(budgetLimit.amount) + parseFloat(budgetLimit.spent) < 0" class="text-danger">
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
  created() {
    this.locale = localStorage.locale ?? 'en-US';
  },
  data() {
    return {
      locale: 'en-US',
    }
  },
  props: {
    budgetLimit: {
      type: Object,
      default: function () {
        return {};
      }
    },
    budget: {
      type: Object,
      default: function () {
        return {};
      }
    }
  }
}
</script>

<style scoped>

</style>