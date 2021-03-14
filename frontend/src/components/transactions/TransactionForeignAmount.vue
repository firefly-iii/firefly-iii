<!--
  - TransactionForeignAmount.vue
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
  <!-- FOREIGN AMOUNT -->
  <div v-if="isVisible" class="form-group">
    <div class="text-xs">{{ $t('form.foreign_amount') }}</div>
    <div class="input-group">
      <input
          v-model="amount"
          :class="errors.length > 0 ? 'form-control is-invalid' : 'form-control'"
          :placeholder="$t('form.foreign_amount')"
          :title="$t('form.foreign_amount')"
          autocomplete="off"
          name="foreign_amount[]"
          type="number"
      >
    </div>
    <span v-if="errors.length > 0">
      <span v-for="error in errors" class="text-danger small">{{ error }}<br/></span>
    </span>
  </div>
</template>

<script>
export default {
  name: "TransactionForeignAmount",
  props: {
    index: {},
    errors: {},
    value: {},
    transactionType: {},
    sourceCurrencyId: {},
    destinationCurrencyId: {},
    fractionDigits: {
      type: Number,
      default: 2
    }
  },
  data() {
    return {
      amount: this.value,
      emitEvent: true
    }
  },
  created() {
    if ('' !== this.amount) {
      this.emitEvent = false;
      this.amount = this.formatNumber(this.amount);
    }
  },
  methods: {
    formatNumber(str) {
      return parseFloat(str).toFixed(this.fractionDigits);
    }
  },
  watch: {
    amount: function (value) {
      if (true === this.emitEvent) {
        this.$emit('set-field', {field: 'foreign_amount', index: this.index, value: value});
      }
      this.emitEvent = true;
    },
    value: function (value) {
      this.amount = value;


    }
  },
  computed: {
    isVisible: {
      get() {
        return !('Transfer' === this.transactionType && this.sourceCurrencyId === this.destinationCurrencyId);
      }
    },
  }
}
</script>

<style scoped>

</style>