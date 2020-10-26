<!--
  - Budget.vue
  - Copyright (c) 2019 james@firefly-iii.org
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
  <div v-if="typeof this.transactionType === 'undefined' || this.transactionType === 'withdrawal' || this.transactionType === 'Withdrawal' || this.transactionType === '' || null === this.transactionType"
       class="form-group"
       v-bind:class="{ 'has-error': hasError()}">
    <div class="col-sm-12 text-sm">
      {{ $t('firefly.budget') }}
    </div>
    <div class="col-sm-12">
      <select
          v-if="this.budgets.length > 0"
          ref="budget"
          v-model="selected"
          :title="$t('firefly.budget')"
          class="form-control"
          name="budget[]"
          @input="handleInput"
          v-on:change="signalChange">
        <option v-for="cBudget in this.budgets"
                :label="cBudget.name"
                :value="cBudget.id">{{ cBudget.name }}
        </option>
      </select>
      <p v-if="this.budgets.length === 1" class="help-block" v-html="$t('firefly.no_budget_pointer')"></p>
      <ul v-for="error in this.error" class="list-unstyled">
        <li class="text-danger">{{ error }}</li>
      </ul>
    </div>
  </div>
</template>

<script>
export default {
  name: "Budget",
  props: {
    transactionType: String,
    value: {
      type: [String, Number],
      default: 0
    },
    error: Array,
    no_budget: String,
  },
  mounted() {
    this.loadBudgets();
  },
  data() {
    return {
      selected: this.value ?? 0,
      budgets: [],
    }
  },
  watch: {
    value: function () {
      console.log('budget: value changed to ' + this.value);
      this.selected = this.value;
    }
  },
  methods: {
    // Fixes edit change budget not updating on every broswer
    signalChange: function (e) {
      this.$emit('input', this.$refs.budget.value);
    },
    handleInput(e) {
      this.$emit('input', this.$refs.budget.value);
    },
    hasError: function () {
      return this.error.length > 0;
    },
    loadBudgets: function () {
      let URI = document.getElementsByTagName('base')[0].href + 'api/v1/autocomplete/budgets?limit=1337';
      axios.get(URI, {}).then((res) => {
        this.budgets = [
          {
            name: this.no_budget,
            id: 0,
          }
        ];
        for (const key in res.data) {
          if (res.data.hasOwnProperty(key) && /^0$|^[1-9]\d*$/.test(key) && key <= 4294967294) {
            this.budgets.push(res.data[key]);
          }
        }
      });
    }
  }
}
</script>
