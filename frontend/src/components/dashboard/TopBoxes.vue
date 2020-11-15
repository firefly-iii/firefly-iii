<!--
  - TopBoxes.vue
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
  <div class="row">
    <div class="col-md-3 col-sm-6 col-12">
      <div class="info-box">
        <span class="info-box-icon"><i class="far fa-bookmark text-info"></i></span>

        <div class="info-box-content">
          <span class="info-box-text">{{ $t("firefly.balance") }}</span>
          <!-- balance in preferred currency -->
          <span class="info-box-number" v-for="balance in prefCurrencyBalances" :title="balance.sub_title">{{ balance.value_parsed }}</span>

          <div class="progress bg-info">
            <div class="progress-bar" style="width: 0"></div>
          </div>
          <!-- balance in not preferred currency -->
          <span class="progress-description">
                        <span v-for="(balance, index) in notPrefCurrencyBalances" :title="balance.sub_title">
                          {{ balance.value_parsed }}<span v-if="index+1 !== notPrefCurrencyBalances.length">, </span>
                        </span>
                      <span v-if="0===notPrefCurrencyBalances.length">&nbsp;</span>
                    </span>
        </div>
      </div>
    </div>

    <div class="col-12 col-sm-6 col-md-3">
      <div class="info-box">
        <span class="info-box-icon"><i class="far fa-calendar-alt text-teal"></i></span>

        <div class="info-box-content">
          <span class="info-box-text">{{ $t('firefly.bills_to_pay') }}</span>

          <!-- bills unpaid, in preferred currency. -->
          <span class="info-box-number" v-for="balance in prefBillsUnpaid">{{ balance.value_parsed }}</span>

          <div class="progress bg-teal">
            <div class="progress-bar" style="width: 0"></div>
          </div>
          <!-- bills unpaid, in other currencies. -->
          <span class="progress-description">
                            <span v-for="(bill, index) in notPrefBillsUnpaid">
                                {{ bill.value_parsed }}<span v-if="index+1 !== notPrefBillsUnpaid.length">, </span>
                            </span>
                            <span v-if="0===notPrefBillsUnpaid.length">&nbsp;</span>
                    </span>
        </div>
      </div>
    </div>

    <div class="clearfix hidden-md-up"></div>

    <!-- left to spend -->
    <div class="col-12 col-sm-6 col-md-3">
      <div class="info-box">
        <span class="info-box-icon"><i class="fas fa-money-bill text-success"></i></span>

        <div class="info-box-content">
          <span class="info-box-text">{{ $t('firefly.left_to_spend') }}</span>

          <!-- left to spend in preferred currency -->
          <span class="info-box-number" v-for="left in prefLeftToSpend" :title="left.sub_title">{{ left.value_parsed }}</span>

          <div class="progress bg-success">
            <div class="progress-bar" style="width: 0"></div>
          </div>
          <!-- other currencies-->
          <span class="progress-description">
                            <span v-for="(left, index) in notPrefLeftToSpend">
                                {{ left.value_parsed }}<span v-if="index+1 !== notPrefLeftToSpend.length">, </span>
                            </span>
                            <span v-if="0===notPrefLeftToSpend.length">&nbsp;</span>
                    </span>
        </div>
      </div>
    </div>

    <!-- net worth -->
    <div class="col-12 col-sm-6 col-md-3">
      <div class="info-box">
        <span class="info-box-icon"><i class="fas fa-money-bill text-success"></i></span>

        <div class="info-box-content">
          <span class="info-box-text"><span>{{ $t('firefly.net_worth') }}</span></span>
          <span class="info-box-number" v-for="nw in prefNetWorth" :title="nw.sub_title">{{ nw.value_parsed }}</span>

          <div class="progress bg-success">
            <div class="progress-bar" style="width: 0"></div>
          </div>
          <span class="progress-description">
                        <span v-for="(nw, index) in notPrefNetWorth">
                                {{ nw.value_parsed }}<span v-if="index+1 !== notPrefNetWorth.length">, </span>
                            </span>
                            <span v-if="0===notPrefNetWorth.length">&nbsp;</span>
                    </span>
        </div>
      </div>
    </div>

  </div>
</template>

<script>
export default {
  name: "TopBoxes",
  props: {},
  data() {
    return {
      currencyPreference: {},
      summary: [],
      balances: [],
      billsPaid: [],
      billsUnpaid: [],
      leftToSpend: [],
      netWorth: [],
    }
  },
  computed: {

    // contains only balances with preferred currency.
    prefCurrencyBalances: function () {
      return this.filterOnCurrency(this.balances);
    },
    notPrefCurrencyBalances: function () {
      return this.filterOnNotCurrency(this.balances);
    },

    // contains only bills unpaid in preferred currency or first one.
    prefBillsUnpaid: function () {
      return this.filterOnCurrency(this.billsUnpaid);
    },
    notPrefBillsUnpaid: function () {
      return this.filterOnNotCurrency(this.billsUnpaid);
    },

    // left to spend
    prefLeftToSpend: function () {
      return this.filterOnCurrency(this.leftToSpend);
    },
    notPrefLeftToSpend: function () {
      return this.filterOnNotCurrency(this.leftToSpend);
    },

    // net worth
    prefNetWorth: function () {
      return this.filterOnCurrency(this.netWorth);
    },
    notPrefNetWorth: function () {
      return this.filterOnNotCurrency(this.netWorth);
    },
  },
  mounted() {
    this.prepareComponent();
    this.currencyPreference = localStorage.currencyPreference ? JSON.parse(localStorage.currencyPreference) : {};
  },
  methods: {
    filterOnCurrency(array) {
      let ret = [];
      for (const key in array) {
        if (array.hasOwnProperty(key)) {
          if (array[key].currency_id === this.currencyPreference.id) {
            ret.push(array[key]);
          }
        }
      }
      // or just the first one:
      if (0 === ret.length && array.hasOwnProperty(0)) {
        ret.push(array[0]);
      }
      return ret;
    },
    filterOnNotCurrency(array) {
      let ret = [];
      for (const key in array) {
        if (array.hasOwnProperty(key)) {
          if (array[key].currency_id !== this.currencyPreference.id) {
            ret.push(array[key]);
          }
        }
      }
      return ret;
    },
    /**
     * Prepare the component.
     */
    prepareComponent() {
      axios.get('./api/v1/summary/basic?start=' + window.sessionStart + '&end=' + window.sessionEnd)
          .then(response => {
            this.summary = response.data;
            this.buildComponent();
          });
    },
    buildComponent() {
      this.getBalanceEntries();
      this.getBillsEntries();
      this.getLeftToSpend();
      this.getNetWorth();
    },

    hasCurrency: function (array) {
      for (const key in array) {
        if (array.hasOwnProperty(key)) {
          if (array[key].currency_id === this.currencyPreference.id) {
            return true;
          }
        }
      }
      return false;
    },

    getBalanceEntries() {
      this.balances = this.getKeyedEntries('balance-in-');
    },
    getNetWorth() {
      this.netWorth = this.getKeyedEntries('net-worth-in-');
    },
    getLeftToSpend() {
      this.leftToSpend = this.getKeyedEntries('left-to-spend-in-');
    },
    getBillsEntries() {
      this.billsPaid = this.getKeyedEntries('bills-paid-in-');
      this.billsUnpaid = this.getKeyedEntries('bills-unpaid-in-');
    },
    getKeyedEntries(expected) {
      let result = [];
      for (const key in this.summary) {
        if (this.summary.hasOwnProperty(key)) {
          if (expected === key.substr(0, expected.length)) {
            result.push(this.summary[key]);
          }
        }
      }
      return result;
    }
  }
}
</script>

<style scoped>

</style>
