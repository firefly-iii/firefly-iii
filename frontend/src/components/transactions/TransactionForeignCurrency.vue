<!--
  - TransactionForeignCurrency.vue
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
  <!-- FOREIGN Currency -->
  <div class="form-group" v-if="selectIsVisible">
    <div class="text-xs">&nbsp;</div>
    <div class="input-group">
      <select name="foreign_currency_id[]" v-model="currencyId" class="form-control">
        <option v-for="currency in selectableCurrencies" :label="currency.name" :value="currency.id">{{ currency.name }}</option>
      </select>
    </div>
  </div>
</template>
<script>

import {createNamespacedHelpers} from "vuex";

const {mapState, mapGetters, mapActions, mapMutations} = createNamespacedHelpers('transactions/create')

export default {
  name: "TransactionForeignCurrency",
  props: ['index'],
  data() {
    return {
      allCurrencies: [],
      selectableCurrencies: [],
      lockedCurrency: 0,
      selectIsVisible: true
    }
  },
  watch: {
    transactionType: function (value) {
      this.lockedCurrency = 0;
      if ('Transfer' === value) {
        // take currency from destination:
        this.currencyId = this.transactions[this.index].destination_account.currency_id;
        this.currencySymbol = this.transactions[this.index].destination_account.currency_symbol;
        this.lockedCurrency = this.currencyId;
      }
      this.filterCurrencies();
      this.checkVisibility();
    },
    destinationAllowedTypes: function (value) {
      this.lockedCurrency = 0;
      if ('Transfer' === this.transactionType) {
        // take currency from destination:
        this.currencyId = this.transactions[this.index].destination_account.currency_id;
        this.currencySymbol = this.transactions[this.index].destination_account.currency_symbol;
        this.lockedCurrency = this.currencyId;
      }
      this.filterCurrencies();
      this.checkVisibility();
    },
    sourceAllowedTypes: function (value) {
      this.lockedCurrency = 0;
      if ('Transfer' === this.transactionType) {
        // take currency from destination:
        this.currencyId = this.transactions[this.index].destination_account.currency_id;
        this.currencySymbol = this.transactions[this.index].destination_account.currency_symbol;
        this.lockedCurrency = this.currencyId;
      }
      this.filterCurrencies();
      this.checkVisibility();
    },

  },
  created: function () {
    this.getAllCurrencies();
  },
  methods: {
    ...mapMutations(
        [
          'updateField',
        ],
    ),
    checkVisibility: function () {
      // have the same currency ID, but not zero, and is a transfer
      let sourceId = this.transactions[this.index].source_account.currency_id;
      let destId = this.transactions[this.index].destination_account.currency_id;
      this.selectIsVisible = true;
      if (sourceId === destId && 0 !== sourceId && 'Transfer' === this.transactionType) {
        this.selectIsVisible = false;
        this.currencyId = 0;
      }
    },

    getAllCurrencies: function () {
      axios.get('./api/v1/autocomplete/currencies')
          .then(response => {
                  this.allCurrencies = response.data;
                  this.filterCurrencies();
                }
          );

    },
    filterCurrencies() {
      // if a currency is locked only that currency can (and must) be selected:
      if (0 !== this.lockedCurrency) {
        for (let key in this.allCurrencies) {
          if (this.allCurrencies.hasOwnProperty(key) && /^0$|^[1-9]\d*$/.test(key) && key <= 4294967294) {
            let current = this.allCurrencies[key];
            if (current.id === this.lockedCurrency) {
              this.selectableCurrencies = [current];
              this.currencyId = current.id;
            }
          }
        }
        return;
      }


      this.selectableCurrencies = [
        {
          "id": 0,
          "name": this.$t('firefly.no_currency')
        }
      ];
      for (let key in this.allCurrencies) {
        if (this.allCurrencies.hasOwnProperty(key) && /^0$|^[1-9]\d*$/.test(key) && key <= 4294967294) {
          let current = this.allCurrencies[key];
          // add to array if not "locked" in place:
          if (this.transactions[this.index].currency_id !== current.id) {
            this.selectableCurrencies.push(current);
          }
          // deselect impossible currency.
          if (this.transactions[this.index].currency_id === current.id && this.currencyId === current.id) {
            this.currencyId = 0;
          }
        }
      }
      //currency_id

      // always add empty currency:
      // this.selectableCurrencies = this.allCurrencies;
      // this.selectableCurrencies.reverse();
      // this.selectableCurrencies.push(
      //   ;
      // this.selectableCurrencies.reverse();

      // remove

    }

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
    currencyId: {
      get() {
        return this.transactions[this.index].foreign_currency_id;
      },
      set(value) {
        this.updateField({field: 'foreign_currency_id', index: this.index, value: value});
      }
    },
    normalCurrencyId: {
      get() {
        return this.transactions[this.index].currency_id;
      },
    },
  }
}
</script>

<style scoped>

</style>