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
  <div class="q-mr-sm">
    <q-card bordered>
      <q-item>
        <q-item-section>
          <q-item-label>{{ $t('firefly.bills_to_pay') }}</q-item-label>
        </q-item-section>
      </q-item>
      <q-separator/>
      <q-card-section horizontal>
        <q-card-section>
          <q-circular-progress
            :value="percentage"
            size="50px"
            :thickness="0.22"
            color="green"
            track-color="grey-3"
          />
        </q-card-section>
        <q-separator vertical/>
        <q-card-section>
          {{ $t('firefly.bills_to_pay') }}:
          <span v-for="(bill, index) in unpaid">
                                {{ formatAmount(bill.code, bill.sum) }}
          <span v-if="index+1 !== unpaid.length">, </span>
                            </span>
          <br/>
          {{ $t('firefly.bills_paid') }}:
          <span v-for="(bill, index) in paid">
                                {{ formatAmount(bill.code, bill.sum) }}
          <span v-if="index+1 !== paid.length">, </span>
                            </span>
        </q-card-section>
      </q-card-section>
      <!--
      <q-card-section class="q-pt-xs">
        <div class="text-overline">

          <span class="float-right">
            <span class="text-grey-4 fas fa-redo-alt" style="cursor: pointer;" @click="triggerForcedUpgrade"></span>
          </span>
        </div>
      </q-card-section>
      <q-card-section class="q-pt-xs">
        <span v-for="(bill, index) in unpaid">
                                {{ formatAmount(bill.code, bill.sum) }}
          <span v-if="index+1 !== unpaid.length">, </span>
                            </span>
      </q-card-section>
      -->
    </q-card>
  </div>
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
      //percentage: 0,
      unpaidAmount: 0.0,
      paidAmount: 0.0,
      range: {
        start: null,
        end: null,
      },
    }
  },
  name: "BillInsightBox",
  computed: {
    percentage: function () {
      if (0 === this.unpaidAmount) {
        return 100;
      }
      const sum = this.unpaidAmount + this.paidAmount;
      if (0.0 === this.paidAmount) {
        return 0;
      }
      return (this.paidAmount / sum) * 100;
    }
  },
  mounted() {
    this.store = useFireflyIIIStore();

    // TODO this code snippet is recycled a lot.
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
    this.triggerUpdate();
  },
  methods: {
    triggerUpdate: function () {
      if (null !== this.store.getRange.start && null !== this.store.getRange.end) {
        this.unpaid = [];
        const start = new Date(this.store.getRange.start);
        const end = new Date(this.store.getRange.end);
        const sum = new Sum;
        sum.unpaid(start, end).then((response) => this.parseUnpaidResponse(response.data));
        sum.paid(start, end).then((response) => this.parsePaidResponse(response.data));
      }
    },
    formatAmount: function (currencyCode, amount) {
      // TODO not yet internationalized
      return Intl.NumberFormat('en-US', {style: 'currency', currency: currencyCode}).format(amount);
    },
    parseUnpaidResponse: function (data) {
      for (let i in data) {
        if (data.hasOwnProperty(i)) {
          const current = data[i];
          this.unpaid.push(
            {
              sum: current.sum,
              code: current.code,
            }
          );
          this.unpaidAmount = this.unpaidAmount + parseFloat(current.sum);
        }
      }
    },
    parsePaidResponse: function (data) {
      for (let i in data) {
        if (data.hasOwnProperty(i)) {
          const current = data[i];
          this.paid.push(
            {
              sum: current.sum,
              code: current.code,
            }
          );
          this.paidAmount = this.paidAmount + (parseFloat(current.sum) * -1);
        }
      }
    }
  }
}
</script>

<style scoped>

</style>
