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
  <div class="form-group" v-if="isVisible">
    <div class="text-xs">{{ $t('form.foreign_amount') }}</div>
    <div class="input-group">
      <input
          :title="$t('form.foreign_amount')"
          autocomplete="off"
          :class="errors.length > 0 ? 'form-control is-invalid' : 'form-control'"
          name="foreign_amount[]"
          type="number"
          v-model="amount"
          :placeholder="$t('form.foreign_amount')"
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
  props: [
      'index',
      'errors',
      'transactionType',
      'sourceCurrencyId',
      'destinationCurrencyId'
  ],
  data() {
    return {
      amount: ''
      // currencySymbol: '',
      // allCurrencies: [],
      // selectableCurrencies: [],
    }
  },
  watch: {
    amount: function(value) {
      this.$emit('set-foreign-amount', {field: 'foreign_amount', index: this.index, value: value});
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