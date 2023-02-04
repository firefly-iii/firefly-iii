<!--
  - BillInsightBox.vue
  - Copyright (c) 2022 james@firefly-iii.org
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
  <!-- TODO most left? q-mr-sm -->
  <!-- TODO middle? dan q-mx-sm -->
  <!-- TODO right? dan q-ml-sm -->
    <q-card bordered flat class="fit">
      <q-item>
        <q-item-section>
          <q-item-label><strong>{{ $t('firefly.bills') }} </strong></q-item-label>
        </q-item-section>
      </q-item>
      <q-separator />
      <q-card-section horizontal>
        <q-card-section>
          <q-circular-progress
            :thickness="0.22"
            :value="percentage"
            color="positive"
            size="50px"
            track-color="negative"
          />
        </q-card-section>
        <q-separator vertical/>
        <q-card-section v-if="0 === unpaid.length && 0 === paid.length">
          {{ $t('firefly.no_bill') }}
        </q-card-section>
        <q-card-section v-if="unpaid.length > 0 || paid.length > 0">
          <span :title="formatAmount(this.currency, this.unpaidAmount)">{{ $t('firefly.bills_to_pay') }}</span>:
          <!-- loop bills to pay -->
          <span v-for="(item, index) in unpaid">
            <span :title="formatAmount(item.native_code, item.native_sum)">
              {{ formatAmount(item.code, item.sum) }}<span v-if="index+1 !== unpaid.length"> + </span></span>
          </span>
          <br/>
          <span v-if="paid.length > 0" :title="formatAmount(this.currency, this.paidAmount)">{{
              $t('firefly.bills_paid')
            }}:</span>
          <span v-for="(item, index) in paid">
            <span :title="formatAmount(item.native_code, item.native_sum)">
              {{ formatAmount(item.code, item.sum) }}
            </span>
            <span v-if="index+1 !== paid.length"> + </span></span>
        </q-card-section>
      </q-card-section>
    </q-card>
</template>

<script>
import {useFireflyIIIStore} from "../../stores/fireflyiii";
import Sum from "../../api/v2/bills/sum";

export default {
  data() {
    return {
      store: null,
      unpaid: [],
      paid: [],
      currency: 'EUR',
      unpaidAmount: 0.0,
      paidAmount: 0.0,
    }
  },
  name: "BillInsightBox",
  computed: {
    percentage: function () {
      if (0 === this.unpaidAmount) {
        return 100;
      }
      if (0.0 === this.paidAmount) {
        return 0;
      }
      const total = this.paidAmount + this.unpaidAmount;
      const pct = (this.paidAmount / total) * 100;
      if (pct > 100) {
        return 100;
      }
      return pct;
    }
  },
  mounted() {
    this.store = useFireflyIIIStore();
    // TODO this code snippet is recycled a lot.
    // subscribe, then update:
    this.store.$onAction(
      ({name, $store, args, after, onError,}) => {
        after((result) => {
          if (name === 'setRange') {
            this.triggerUpdate();
          }
        })
      }
    )
    this.triggerUpdate();
  },
  methods: {
    triggerUpdate: function () {
      if (null !== this.store.getRange.start && null !== this.store.getRange.end) {
        this.unpaid = [];
        const start = new Date(this.store.getRange.start);
        const end = new Date(this.store.getRange.end);
        const sum = new Sum;
        this.currency = this.store.getCurrencyCode;
        sum.unpaid(start, end).then((response) => this.parseUnpaidResponse(response.data));
        sum.paid(start, end).then((response) => this.parsePaidResponse(response.data));
      }
    },
    // TODO this method is recycled a lot.
    formatAmount: function (currencyCode, amount) {
      return Intl.NumberFormat(this.store.getLocale, {style: 'currency', currency: currencyCode}).format(amount);
    },
    parseUnpaidResponse: function (data) {
      for (let i in data) {
        if (data.hasOwnProperty(i)) {
          const current = data[i];
          const hasNative = current.converted && current.native_id !== current.id && parseFloat(current.native_sum) !== 0.0;
          this.unpaid.push(
            {
              sum: current.sum,
              code: current.code,
              native_sum: current.converted ? current.native_sum : current.sum,
              native_code: current.converted ? current.native_code : current.code,
              native: hasNative,
            }
          );
          if (current.converted && (hasNative || current.native_id === current.id)) {
            this.unpaidAmount = this.unpaidAmount + parseFloat(current.native_sum);
          }
          if (!current.converted) {
            this.unpaidAmount = this.unpaidAmount + parseFloat(current.sum);
          }
        }
      }
    },
    parsePaidResponse: function (data) {
      for (let i in data) {
        if (data.hasOwnProperty(i)) {
          const current = data[i];
          const hasNative = current.converted && current.native_id !== current.id && parseFloat(current.native_sum) !== 0.0;
          this.paid.push(
            {
              sum: current.sum,
              code: current.code,
              native_sum: current.converted ? current.native_sum : current.sum,
              native_code: current.converted ? current.native_code : current.code,
              native: hasNative,
            }
          );
          if (current.converted && (hasNative || current.native_id === current.id)) {
            this.paidAmount = this.paidAmount + (parseFloat(current.native_sum) * -1);
          }
          if (!current.converted) {
            this.paidAmount = this.paidAmount + (parseFloat(current.sum) * -1);
          }
        }
      }
    }
  }
}
</script>
