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
      <a :href="'./budgets/show/' + budget.id">{{ budget.name }}</a>
    </td>
    <td class="align-middle text-right">
      <span class="text-danger">
        {{ Intl.NumberFormat(locale, {style: 'currency', currency: budget.currency_code}).format(parseFloat(budget.spent)) }}
      </span>
    </td>
  </tr>
</template>

<script>
export default {
  name: "BudgetRow",
  props: {
    budget: {
      type: Object,
      default: {}
    }
  }
}
</script>

<style scoped>

</style>