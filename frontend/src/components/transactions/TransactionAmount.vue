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
      <div class="input-group-prepend">
        <div class="input-group-text">{{ this.currencySymbol }}</div>
      </div>
      <input
          title="Amount"
          autocomplete="off"
          autofocus
          class="form-control"
          name="amount[]"
          type="number"
          placeholder="Amount"
      >
    </div>
  </div>
</template>

<script>

import {createNamespacedHelpers} from "vuex";

const {mapState, mapGetters, mapActions, mapMutations} = createNamespacedHelpers('transactions/create')
//const {mapRootState, mapRootGetters, mapRootActions, mapRootMutations} = createHelpers('');


export default {
  name: "TransactionAmount",
  data() {
    return {
      currencySymbol: '',
    }
  },
  watch: {
    selectedTransactionType: function (value) {
      console.log('TransactionAmount just noticed transaction type is now ' + value);
    }
  },
  created: function() {
    console.log('TransactionAmount is created.');
    this.updateCurrency();
  },
  methods: {
    updateCurrency: function() {
      if('any' === this.transactionType) {
        // use default currency from store.
        this.currencySymbol = this.currencyPreference.symbol;

      }
    }
  },
  computed: {
    currencyPreference: {
      get() {
        return this.$store.state.currencyPreference;
      }
    },
    ...mapGetters([
                    'transactionType',
                    'transactions',
                  ]),
    selectedTransactionType: {
      get() {
        return this.transactionType;
      },
      set(value) {
        // console.log('set selectedAccount for ' + this.direction);
        // console.log(value);
        // this.updateField({field: this.accountKey, index: this.index, value: value});
      }
    }
  }
}
</script>

<style scoped>

</style>