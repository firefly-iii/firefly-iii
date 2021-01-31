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
  <div class="form-group">
    <input type="hidden" name="foreign_currency_id[]" :value="currencyId"/>
    <div class="text-xs">{{ $t('form.foreign_amount') }}</div>
    <div class="input-group">
      <input
          :title="$t('form.foreign_amount')"
          autocomplete="off"
          :class="errors.length > 0 ? 'form-control is-invalid' : 'form-control'"
          :disabled="0===currencyId"
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

import {createNamespacedHelpers} from "vuex";

const {mapState, mapGetters, mapActions, mapMutations} = createNamespacedHelpers('transactions/create')
//const {mapRootState, mapRootGetters, mapRootActions, mapRootMutations} = createHelpers('');


export default {
  name: "TransactionForeignAmount",
  props: ['index','errors'],
  data() {
    return {
      currencySymbol: '',
      allCurrencies: [],
      selectableCurrencies: [],
    }
  },
  watch: {
    transactionType: function (value) {
      // switch (value) {
      //   case 'Transfer':
      //   case 'Withdrawal':
      //     // take currency from source:
      //     //this.currencyId = this.transactions[this.index].source_account.currency_id;
      //     this.currencySymbol = this.transactions[this.index].source_account.currency_symbol;
      //     return;
      //   case 'Deposit':
      //     // take currency from destination:
      //     this.currencyId = this.transactions[this.index].destination_account.currency_id;
      //     this.currencySymbol = this.transactions[this.index].destination_account.currency_symbol;
      //     return;
      // }
    },
    destinationAllowedTypes: function (value) {
      // // aka source was updated. if source is asset/loan/debt/mortgage use it to set the currency:
      // if ('undefined' !== typeof this.transactions[this.index].source_account.type) {
      //   if (['Asset account', 'Loan', 'Debt', 'Mortgage'].indexOf(this.transactions[this.index].source_account.type) !== -1) {
      //     // get currency pref from source account
      //     this.currencyId = this.transactions[this.index].source_account.currency_id;
      //     this.currencySymbol = this.transactions[this.index].source_account.currency_symbol;
      //   }
      // }
    },
    sourceAllowedTypes: function (value) {
      // // aka destination was updated. if destination is asset/loan/debt/mortgage use it to set the currency:
      // // unless its already known to be a transfer
      // if ('undefined' !== typeof this.transactions[this.index].destination_account.type && 'Transfer' !== this.transactionType) {
      //   if (['Asset account', 'Loan', 'Debt', 'Mortgage'].indexOf(this.transactions[this.index].destination_account.type) !== -1) {
      //     // get currency pref from destination account
      //     this.currencyId = this.transactions[this.index].destination_account.currency_id;
      //     this.currencySymbol = this.transactions[this.index].destination_account.currency_symbol;
      //   }
      // }
    },

  },
  created: function () {
  },
  methods: {
    ...mapMutations(
        [
          'updateField',
        ],
    ),

    // updateCurrency: function () {
    //   if (0 === this.currencyId) {
    //     // use default currency from store.
    //     this.currencySymbol = this.currencyPreference.symbol;
    //     this.currencyId = this.currencyPreference.id;
    //   }
    // }
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
                    'destinationAllowedTypes',
                    'sourceAllowedTypes',
                  ]),
    amount: {
      get() {
        return this.transactions[this.index].foreign_amount;
      },
      set(value) {
        this.updateField({field: 'foreign_amount', index: this.index, value: value});
      }
    },
    currencyId: {
      get() {
        return this.transactions[this.index].foreign_currency_id;
      },
      set(value) {
        this.updateField({field: 'foreign_currency_id', index: this.index, value: value});
      }
    },
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