<!--
  - Amount.vue
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
  <div class="form-group" v-bind:class="{ 'has-error': hasError()}">
    <div class="col-sm-8 col-sm-offset-4 text-sm">
      {{ $t('firefly.amount') }}
    </div>
    <label ref="cur" class="col-sm-4 control-label"></label>
    <div class="col-sm-8">
      <div class="input-group">
        <input ref="amount"
               :title="$t('firefly.amount')"
               :value="value"
               autocomplete="off"
               class="form-control"
               name="amount[]"
               step="any"
               type="number"
               v-bind:placeholder="$t('firefly.amount')"
               @input="handleInput">

        <span class="input-group-btn">
            <button
                class="btn btn-default"
                tabIndex="-1"
                type="button"
                v-on:click="clearAmount"><i class="fa fa-trash-o"></i></button>
        </span>
      </div>
    </div>

    <ul v-for="error in this.error" class="list-unstyled">
      <li class="text-danger">{{ error }}</li>
    </ul>
  </div>
</template>

<script>
export default {
  name: "Amount",
  props: ['source', 'destination', 'transactionType', 'value', 'error'],
  data() {
    return {
      sourceAccount: this.source,
      destinationAccount: this.destination,
      type: this.transactionType
    }
  },
  methods: {
    handleInput(e) {
      this.$emit('input', this.$refs.amount.value);
    },
    clearAmount: function () {
      this.$refs.amount.value = '';
      this.$emit('input', this.$refs.amount.value);
      // some event?
      this.$emit('clear:amount')
    },
    hasError: function () {
      return this.error.length > 0;
    },
    changeData: function () {
      //console.log('Triggered amount changeData()');
      let transactionType = this.transactionType;
      // reset of all are empty:
      if (!transactionType && !this.source.name && !this.destination.name) {
        $(this.$refs.cur).text('');

        return;
      }
      if (null === transactionType) {
        transactionType = '';
      }
      if ('' === transactionType && '' !== this.source.currency_name) {
        $(this.$refs.cur).text(this.source.currency_name);
        return;
      }
      if ('' === transactionType && '' !== this.destination.currency_name) {
        $(this.$refs.cur).text(this.destination.currency_name);
        return;
      }
      // for normal transactions, the source leads the currency
      if (transactionType.toLowerCase() === 'withdrawal' ||
          transactionType.toLowerCase() === 'reconciliation' ||
          transactionType.toLowerCase() === 'transfer') {
        $(this.$refs.cur).text(this.source.currency_name);
        return;
      }
      // for deposits, the destination leads the currency
      // but source must not be a liability
      if (transactionType.toLowerCase() === 'deposit'
          &&
          !('debt' === this.source.type.toLowerCase() ||
              'loan' === this.source.type.toLowerCase() ||
              'mortgage' === this.source.type.toLowerCase()
          )
      ) {
        $(this.$refs.cur).text(this.destination.currency_name);
      }
      // for deposits, the destination leads the currency
      // unless source is liability, then source leads:
      if (transactionType.toLowerCase() === 'deposit'
          &&
          ('debt' === this.source.type.toLowerCase() ||
              'loan' === this.source.type.toLowerCase() ||
              'mortgage' === this.source.type.toLowerCase()
          )
      ) {
        $(this.$refs.cur).text(this.source.currency_name);
      }
    }
  },
  watch: {
    source: function () {
      // console.log('amount: watch source triggered');
      this.changeData();
    },
    value: function () {
      // console.log('amount: value changed');
    },
    destination: function () {
      // console.log('amount: watch destination triggered');
      this.changeData();
    },
    transactionType: function () {
      // console.log('amount: watch transaction type triggered');
      this.changeData();
    }
  },
  mounted() {
    // console.log('amount: mounted');
    this.changeData();
  }
}
</script>
