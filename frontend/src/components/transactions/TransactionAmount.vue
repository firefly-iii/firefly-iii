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
      <div class="input-group-prepend" v-if="currencySymbol">
        <div class="input-group-text">{{ currencySymbol }}</div>
      </div>
      <input
          :title="$t('firefly.amount')"
          autocomplete="off"
          :class="errors.length > 0 ? 'form-control is-invalid' : 'form-control'"
          name="amount[]"
          type="number"
          v-model="transactionAmount"
          :placeholder="$t('firefly.amount')"
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
  props: [
      'index', 'errors', 'amount', 'transactionType',
      'sourceCurrencySymbol',
      'destinationCurrencySymbol',
  ],
  data() {
    return {
      transactionAmount: this.amount,
      currencySymbol: null,
      srcCurrencySymbol: this.sourceCurrencySymbol,
      dstCurrencySymbol: this.destinationCurrencySymbol,
    }
  },
  watch: {
    transactionAmount: function (value) {
      this.$emit('set-amount', value);
    },
    amount: function(value) {
      this.transactionAmount = value;
    },
    sourceCurrencySymbol: function (value) {
      this.srcCurrencySymbol = value;
    },
    destinationCurrencySymbol: function (value) {
      this.dstCurrencySymbol = value;
    },

    transactionType: function(value) {
        switch (value) {
          case 'Transfer':
          case 'Withdrawal':
            this.currencySymbol =this.srcCurrencySymbol;
            break;
          case 'Deposit':
            this.currencySymbol =this.dstCurrencySymbol;
        }
    },
  },
}
</script>

<style scoped>

</style>