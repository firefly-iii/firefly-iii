<!--
  - TransactionAmount.vue
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
    <div class="text-xs">{{ $t('firefly.amount') }}</div>
    <div class="input-group">
      <div v-if="currencySymbol" class="input-group-prepend">
        <div class="input-group-text">{{ currencySymbol }}</div>
      </div>
      <input
          v-model="transactionAmount"
          :class="errors.length > 0 ? 'form-control is-invalid' : 'form-control'"
          :placeholder="$t('firefly.amount')"
          :title="$t('firefly.amount')"
          autocomplete="off"
          name="amount[]"
          type="number"
          step="any"
      >
    </div>
    <span v-if="errors.length > 0">
      <span v-for="error in errors" class="text-danger small">{{ error }}<br/></span>
    </span>
  </div>
</template>

<script>

export default {
  name: "TransactionAmount",
  props: {
    index: {
      type: Number,
      default: 0,
      required: true
    },
    errors: {},
    amount: {},
    transactionType: {},
    sourceCurrencySymbol: {},
    destinationCurrencySymbol: {},
    fractionDigits: {
      default: 2,
      required: false
    },
  },
  created() {
    if ('' !== this.amount) {
      this.emitEvent = false;
      this.transactionAmount = this.formatNumber(this.amount);
    }
  },
  methods: {
    formatNumber(str) {
      return parseFloat(str).toFixed(this.fractionDigits);
    }
  },
  data() {
    return {
      transactionAmount: this.amount,
      currencySymbol: null,
      srcCurrencySymbol: this.sourceCurrencySymbol,
      dstCurrencySymbol: this.destinationCurrencySymbol,
      emitEvent: true
    }
  },
  watch: {
    transactionAmount: function (value) {
      if (true === this.emitEvent) {
        this.$emit('set-field', {field: 'amount', index: this.index, value: value});
      }
      this.emitEvent = true;
    },
    amount: function (value) {
      this.transactionAmount = value;
    },
    sourceCurrencySymbol: function (value) {
      this.srcCurrencySymbol = value;
    },
    destinationCurrencySymbol: function (value) {
      this.dstCurrencySymbol = value;
    },
    transactionType: function (value) {
      switch (value) {
        case 'Transfer':
        case 'Withdrawal':
          this.currencySymbol = this.srcCurrencySymbol;
          break;
        case 'Deposit':
          this.currencySymbol = this.dstCurrencySymbol;
      }
    },
  },
}
</script>

<style scoped>

</style>