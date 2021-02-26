<!--
  - BudgetListGroup.vue
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
      <h3 class="card-title">{{ title }}</h3>
    </div>
    <div class="card-body table-responsive p-0">
      <table class="table table-sm">
        <tbody>
        <BudgetLimitRow v-for="(budgetLimit, key) in budgetLimits" v-bind:key="key" :budgetLimit="budgetLimit"/>
        <BudgetRow v-for="(budget, key) in budgets" v-bind:key="key" :budget="budget"/>
        </tbody>
      </table>
    </div>
    <div class="card-footer">
      <a class="btn btn-default button-sm" href="./budgets"><i class="far fa-money-bill-alt"></i> {{ $t('firefly.go_to_budgets') }}</a>
    </div>
  </div>
</template>

<script>
import BudgetLimitRow from "./BudgetLimitRow";
import BudgetRow from "./BudgetRow";

export default {
  name: "BudgetListGroup",
  components: {BudgetLimitRow, BudgetRow},
  props: {
    title: String,
    budgetLimits: Array,
    budgets: Array,
  },
}
</script>

<style scoped>

</style>