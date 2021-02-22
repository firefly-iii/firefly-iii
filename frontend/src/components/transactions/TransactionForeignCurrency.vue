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
  <div class="form-group" v-if="isVisible">
    <div class="text-xs">&nbsp;</div>
    <div class="input-group">
      <select name="foreign_currency_id[]" v-model="selectedCurrency" class="form-control">
        <option v-for="currency in selectableCurrencies" :label="currency.name" :value="currency.id">{{ currency.name }}</option>
      </select>
    </div>
  </div>
</template>
<script>

export default {
  name: "TransactionForeignCurrency",
  props: [
    'index',
    'transactionType',
    'sourceCurrencyId',
    'destinationCurrencyId',
    'selectedCurrencyId'
  ],
  data() {
    return {
      selectedCurrency: 0,
      allCurrencies: [],
      selectableCurrencies: [],
      dstCurrencyId: this.destinationCurrencyId,
      srcCurrencyId: this.sourceCurrencyId,
      lockedCurrency: 0,
    }
  },
  watch: {
    sourceCurrencyId: function (value) {
      this.srcCurrencyId = value;
    },
    destinationCurrencyId: function (value) {
      this.dstCurrencyId = value;
    },
    selectedCurrency: function(value) {
      this.$emit('set-foreign-currency-id', value);
    },
    transactionType: function (value) {
      this.lockedCurrency = 0;
      if ('Transfer' === value) {
        this.lockedCurrency = this.dstCurrencyId;
        this.selectedCurrency = this.dstCurrencyId;
      }
      this.filterCurrencies();
    },
  },
  created: function () {
    this.getAllCurrencies();
  },
  methods: {
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
              this.selectedCurrency = current.id;
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
          this.selectableCurrencies.push(current);
        }
      }
    }
  },
  computed: {
    isVisible: function () {
      return !('Transfer' === this.transactionType && this.srcCurrencyId === this.dstCurrencyId);
    }
  }
}
</script>

<style scoped>

</style>