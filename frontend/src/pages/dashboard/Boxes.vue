<!--
  - Boxes.vue
  - Copyright (c) 2021 james@firefly-iii.org
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
    <div class="col-4 q-pr-sm q-pr-sm">
      <q-card bordered>
        <q-card-section class="q-pt-xs">
          <div class="text-overline">
            {{ $t('firefly.bills_to_pay') }}
            <span class="float-right">
            <span class="text-grey-4 fas fa-redo-alt" style="cursor: pointer;" @click="triggerForcedUpgrade"></span>
              </span>
          </div>
        </q-card-section>
        <q-card-section class="q-pt-xs">
          <span v-for="balance in prefBillsUnpaid">{{ balance.value_parsed }}</span>
          <span v-for="(bill, index) in notPrefBillsUnpaid">
                                {{ bill.value_parsed }}<span v-if="index+1 !== notPrefBillsUnpaid.length">, </span>
                            </span>
        </q-card-section>
      </q-card>
    </div>
    <div class="col-4 q-pr-sm q-pl-sm">

      <q-card bordered>
        <q-card-section class="q-pt-xs">
          <div class="text-overline">
            {{ $t('firefly.left_to_spend') }}
            <span class="float-right">
            <span class="text-grey-4 fas fa-redo-alt" style="cursor: pointer;" @click="triggerForcedUpgrade"></span>
              </span>
          </div>
        </q-card-section>
        <q-card-section class="q-pt-xs">
          <!-- left to spend in preferred currency -->
          <span v-for="left in prefLeftToSpend" :title="left.sub_title">{{ left.value_parsed }}</span>
          <span v-for="(left, index) in notPrefLeftToSpend">
                                {{ left.value_parsed }}<span v-if="index+1 !== notPrefLeftToSpend.length">, </span>
                            </span>
        </q-card-section>
      </q-card>
    </div>
    <div class="col-4 q-pl-sm">
      <q-card bordered>
        <q-card-section class="q-pt-xs">
          <div class="text-overline">
            {{ $t('firefly.net_worth') }}
            <span class="float-right">
            <span class="text-grey-4 fas fa-redo-alt" style="cursor: pointer;" @click="triggerForcedUpgrade"></span>
              </span>
          </div>
        </q-card-section>
        <q-card-section class="q-pt-xs">
          <span v-for="nw in prefNetWorth" :title="nw.sub_title">{{ nw.value_parsed }}</span>
          <span v-for="(nw, index) in notPrefNetWorth">
                                {{ nw.value_parsed }}<span v-if="index+1 !== notPrefNetWorth.length">, </span>
                            </span>
          <span v-if="0===notPrefNetWorth.length">&nbsp;</span>
        </q-card-section>
      </q-card>
    </div>
  </div>
</template>

<script>
import Basic from "src/api/summary/basic";
import {useFireflyIIIStore} from '../../stores/fireflyiii'

export default {
  name: 'Boxes',
  computed: {
    prefBillsUnpaid: function () {
      return this.filterOnCurrency(this.billsUnpaid);
    },
    notPrefBillsUnpaid: function () {
      return this.filterOnNotCurrency(this.billsUnpaid);
    },
    prefLeftToSpend: function () {
      return this.filterOnCurrency(this.leftToSpend);
    },
    notPrefLeftToSpend: function () {
      return this.filterOnNotCurrency(this.leftToSpend);
    },
    prefNetWorth: function () {
      return this.filterOnCurrency(this.netWorth);
    },
    notPrefNetWorth: function () {
      return this.filterOnNotCurrency(this.netWorth);
    },
  },
  created() {
  },
  data() {
    return {
      summary: [],
      billsPaid: [],
      billsUnpaid: [],
      leftToSpend: [],
      netWorth: [],
      range: {
        start: null,
        end: null,
      },
      store: null
    }
  },
  mounted() {
    this.store = useFireflyIIIStore();

    if (null === this.range.start || null === this.range.end) {

      // subscribe, then update:
      this.store.$onAction(
        ({name, $store, args, after, onError,}) => {
          after((result) => {
            if (name === 'setRange') {
              this.range = result;
              this.triggerUpdate();
            }
          })
        }
      )
    }

    if (null !== this.store.getRange.start && null !== this.store.getRange.end) {
      this.start = this.store.getRange.start;
      this.end = this.store.getRange.end;
      this.triggerUpdate();
    }
  },
  methods: {
    triggerForcedUpgrade: function () {
      this.store.refreshCacheKey();
      this.triggerUpdate();
    },
    triggerUpdate: function () {
      if (null !== this.store.getRange.start && null !== this.store.getRange.end) {
        const basic = new Basic;
        basic.list({start: this.store.getRange.start, end: this.store.getRange.end}, this.store.getCacheKey).then(data => {
          this.netWorth = this.getKeyedEntries(data.data, 'net-worth-in-');
          this.leftToSpend = this.getKeyedEntries(data.data, 'left-to-spend-in-');
          this.billsPaid = this.getKeyedEntries(data.data, 'bills-paid-in-');
          this.billsUnpaid = this.getKeyedEntries(data.data, 'bills-unpaid-in-');
        });
      }
    },
    getKeyedEntries(array, expected) {
      let result = [];
      for (const key in array) {
        if (array.hasOwnProperty(key)) {
          if (expected === key.substr(0, expected.length)) {
            result.push(array[key]);
          }
        }
      }
      return result;
    },
    filterOnCurrency(array) {
      let ret = [];
      for (const key in array) {
        if (array.hasOwnProperty(key)) {
          if (array[key].currency_id === this.store.getCurrencyId) {
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
          if (array[key].currency_id !== this.store.getCurrencyId) {
            ret.push(array[key]);
          }
        }
      }
      return ret;
    },
  }
}
</script>
