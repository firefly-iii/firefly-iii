<!--
  - NetWorthInsightBox.vue
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
          <q-item-label><strong>{{ $t('firefly.net_worth') }}</strong></q-item-label>
        </q-item-section>
      </q-item>
      <q-separator/>
      <q-card-section horizontal>
        <q-card-section>
          <q-icon :color="primary > 0 ? 'positive' : 'negative'" name="fas fa-chart-line" size="50px"/>
        </q-card-section>
        <q-separator vertical/>
        <q-card-section>
          <strong>{{ formatAmount(currency, primary) }}</strong><br/>
          <small>
             <span v-for="(item, index) in netWorth">
                <span :title="formatAmount(item.native_code, item.native_sum)">{{
                    formatAmount(item.code, item.sum)
                  }}</span>
                <span v-if="index+1 !== netWorth.length"> + </span></span>
          </small>
        </q-card-section>
      </q-card-section>
    </q-card>
</template>

<script>
import {useFireflyIIIStore} from "../../stores/fireflyiii";
import NetWorth from "../../api/v2/net-worth";

export default {
  name: "NetWorthInsightBox",
  data() {
    return {
      netWorth: [],
      primary: 0,
      currency: 'EUR',
      store: null,
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
        const now = new Date;
        let date = end;
        if (now >= start && now <= end) {
          date = now;
        }
        this.currency = this.store.getCurrencyCode;
        (new NetWorth).get(date).then((response) => this.parseResponse(response.data));
      }
    },
    parseResponse(data) {
      for (let i in data) {
        if (data.hasOwnProperty(i)) {
          const current = data[i];

          const hasNative = current.converted && current.native_id !== current.id && parseFloat(current.native_sum) !== 0.0;
          if (current.converted && (hasNative || current.native_id === current.id)) {
            this.primary = this.primary + parseFloat(current.native_sum);
          }
          if (!current.converted) {
            this.primary = this.primary + parseFloat(current.sum);
          }
          if (parseFloat(current.sum) !== 0.0) {
            this.netWorth.push(
              {
                sum: current.sum,
                code: current.code,
                native_sum: current.converted ? current.native_sum : current.sum,
                native_code: current.converted ? current.native_code : current.code,
                native: hasNative,
              }
            );
          }
        }
      }
    },
    // TODO this method is recycled a lot.
    formatAmount: function (currencyCode, amount) {
      return Intl.NumberFormat(this.store?.getLocale ?? 'en-US', {
        style: 'currency',
        currency: currencyCode
      }).format(amount);
    },
  },
}
</script>

<style scoped>

</style>
