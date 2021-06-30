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
  <div v-if="isVisible" class="form-group">
    <div class="text-xs">&nbsp;</div>
    <div class="input-group">
      <select v-model="selectedCurrency" class="form-control" name="foreign_currency_id[]">
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
    'selectedCurrencyId',
    'value'
  ],
  data() {
    return {
      selectedCurrency: this.value,
      allCurrencies: [],
      selectableCurrencies: [],
      dstCurrencyId: this.destinationCurrencyId,
      srcCurrencyId: this.sourceCurrencyId,
      lockedCurrency: 0,
      emitEvent: true
    }
  },
  watch: {
    value: function (value) {
      this.selectedCurrency = value;
    },
    sourceCurrencyId: function (value) {
      // console.log('Watch sourceCurrencyId');
      this.srcCurrencyId = value;
      this.lockCurrency();
    },
    destinationCurrencyId: function (value) {
      // console.log('Watch destinationCurrencyId');
      this.dstCurrencyId = value;
      this.lockCurrency();
    },
    selectedCurrency: function (value) {
      this.$emit('set-field', {field: 'foreign_currency_id', index: this.index, value: value});
    },
    transactionType: function (value) {
      this.lockCurrency();
    },
  },
  created: function () {
    // console.log('Created TransactionForeignCurrency');
    this.getAllCurrencies();
  },
  methods: {
    lockCurrency: function () {
      // console.log('Lock currency (' + this.transactionType + ')');
      this.lockedCurrency = 0;
      if ('transfer' === this.transactionType.toLowerCase()) {
        // console.log('IS a transfer!');
        this.lockedCurrency = parseInt(this.dstCurrencyId);
        this.selectedCurrency = parseInt(this.dstCurrencyId);
      }
      this.filterCurrencies();
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
      // console.log('filterCurrencies');
      // console.log(this.lockedCurrency);
      // if a currency is locked only that currency can (and must) be selected:
      if (0 !== this.lockedCurrency) {
        // console.log('Here we are');
        for (let key in this.allCurrencies) {
          if (this.allCurrencies.hasOwnProperty(key) && /^0$|^[1-9]\d*$/.test(key) && key <= 4294967294) {
            let current = this.allCurrencies[key];
            if (parseInt(current.id) === this.lockedCurrency) {
              this.selectableCurrencies = [current];
              this.selectedCurrency = current.id;
            }
          }
        }
        // if source + dest ID are the same, skip the whole field.

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
      return !('transfer' === this.transactionType.toLowerCase() && parseInt(this.srcCurrencyId) === parseInt(this.dstCurrencyId));
    }
  }
}
</script>

