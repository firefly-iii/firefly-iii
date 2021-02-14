<!--
  - TransactionBudget.vue
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
  <div class="form-group">
    <div class="text-xs d-none d-lg-block d-xl-block">
      {{ $t('firefly.budget') }}
    </div>
    <div class="input-group">
      <select
          ref="budget"
          :title="$t('firefly.budget')"
          v-model="budget"
          autocomplete="off"
          :class="errors.length > 0 ? 'form-control is-invalid' : 'form-control'"
          name="budget_id[]"
          v-on:submit.prevent
      >
        <option v-for="budget in this.budgetList" :value="budget.id" :label="budget.name">{{ budget.name }}</option>
      </select>
    </div>
    <span v-if="errors.length > 0">
      <span v-for="error in errors" class="text-danger small">{{ error }}<br/></span>
    </span>
  </div>
</template>

<script>

import {createNamespacedHelpers} from "vuex";

const {mapState, mapGetters, mapActions, mapMutations} = createNamespacedHelpers('transactions/create')

export default {
  props: ['index', 'value', 'errors'],
  name: "TransactionBudget",
  data() {
    return {
      budgetList: [],
      budget: this.value
    }
  },
  created() {
    this.collectData();
  },
  methods: {
    ...mapMutations(
        [
          'updateField',
        ],
    ),
    collectData() {
      this.budgetList.push(
          {
            id: 0,
            name: this.$t('firefly.no_budget'),
          }
      );
      this.getBudgets();
    },
    getBudgets() {
      axios.get('./api/v1/budgets')
          .then(response => {
                  this.parseBudgets(response.data);
                }
          );
    },
    parseBudgets(data) {
      for (let key in data.data) {
        if (data.data.hasOwnProperty(key) && /^0$|^[1-9]\d*$/.test(key) && key <= 4294967294) {
          let current = data.data[key];
          this.budgetList.push(
              {
                id: parseInt(current.id),
                name: current.attributes.name
              }
          );
        }
      }
    },
  },
  watch: {
    budget: function (value) {
      this.updateField({field: 'budget_id', index: this.index, value: value});
    }
  },
  computed: {
    ...mapGetters(
        [
          'transactionType',
          'transactions',
        ]
    )
  }
}
</script>
