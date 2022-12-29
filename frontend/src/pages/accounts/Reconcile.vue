<!--
  - Reconcile.vue
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
  <q-page>
    <div v-if="!canReconcile" class="row q-mx-md">
      <div class="col-12">
        <q-card bordered>
          <q-card-section>
            This account cannot be reconciled :(
          </q-card-section>
        </q-card>
      </div>
    </div>
    <div class="row q-mx-md">
      <div class="col-9 q-pr-xs">
        <q-card bordered>
          <q-card-section>
            <div class="text-h6">Reconcilliation range</div>
          </q-card-section>
          <q-card-section>
            <div class="row">
              <div class="col-3 q-pr-xs">
                <q-input v-model="startDate" dense hint="Start date" outlined type="date">
                  <template v-slot:prepend>
                    <q-icon name="far fa-calendar"/>
                  </template>
                </q-input>
              </div>
              <div class="col-3 q-px-xs">
                <q-input v-model="startBalance" dense hint="Start balance" outlined step="0.00" type="number">
                  <template v-slot:prepend>
                    <q-icon name="fas fa-coins"/>
                  </template>
                </q-input>
              </div>
              <div class="col-3">
                <q-input v-model="endDate" dense hint="End date" outlined type="date">
                  <template v-slot:prepend>
                    <q-icon name="far fa-calendar"/>
                  </template>
                </q-input>
              </div>
              <div class="col-3 q-px-xs">
                <q-input v-model="endBalance" dense hint="End Balance" outlined step="0.00" type="number">
                  <template v-slot:prepend>
                    <q-icon name="fas fa-coins"/>
                  </template>
                </q-input>
              </div>
            </div>
          </q-card-section>
          <q-card-section>
            <div class="row">
              <div class="col-9 q-px-xs">
                Match the amounts and dates above to your bank statement, and press "Start reconciling"
              </div>
              <div class="col-3 q-px-xs">
                <q-btn @click="initReconciliation">Start reconciling</q-btn>
              </div>
            </div>
          </q-card-section>

        </q-card>
      </div>
      <div class="col-3 q-pl-xs">
        <q-card bordered>
          <q-card-section>
            <div class="text-h6">Options</div>
          </q-card-section>
          <q-card-section>
            EUR {{ balanceDiff }}
          </q-card-section>
          <q-card-actions>
            Actions
          </q-card-actions>
        </q-card>
      </div>
    </div>
    <div class="row q-ma-md">
      <div class="col">
        <q-card bordered>
          <q-card-section>
            <div class="text-h6">
              First verify the date-range and balances. Then press "Start reconciling"
            </div>
          </q-card-section>
        </q-card>
        <p>&nbsp;</p>
        <p>&nbsp;</p>
        <p>&nbsp;</p>
        <p>&nbsp;</p>
        <p>&nbsp;</p>
        <p>&nbsp;</p>
        <p>&nbsp;</p>
        <p>&nbsp;</p>
        <p>&nbsp;</p>
        <p>&nbsp;</p>
        <p>&nbsp;</p>
        <p>&nbsp;</p>
        <p>&nbsp;</p>
        <p>&nbsp;</p>
        <p>&nbsp;</p>
        <p>&nbsp;</p>
        <p>&nbsp;</p>
        <p>&nbsp;</p>
        <p>&nbsp;</p>
        <p>&nbsp;</p>
        <p>&nbsp;</p>
        <p>&nbsp;</p>
        <p>&nbsp;</p>
        <p>&nbsp;</p>
        <p>&nbsp;</p>
        <p>&nbsp;</p>
        <p>&nbsp;</p>
        <p>&nbsp;</p>
        <p>&nbsp;</p>
        <p>&nbsp;</p>
        <p>&nbsp;</p>
        <p>&nbsp;</p>
        <p>&nbsp;</p>
        <p>&nbsp;</p>
        <p>&nbsp;</p>
        <p>&nbsp;</p>
        <p>&nbsp;</p>
        <p>&nbsp;</p>
        <p>&nbsp;</p>
        <p>&nbsp;</p>
        <p>&nbsp;</p>
        <p>&nbsp;</p>
        <p>&nbsp;</p>
        <p>&nbsp;</p>
        <p>&nbsp;</p>
        <p>&nbsp;</p>
        <p>&nbsp;</p>
        <p>&nbsp;</p>
        <p>&nbsp;</p>
        <p>&nbsp;</p>
        <p>&nbsp;</p>
        <p>&nbsp;</p>
        <p>&nbsp;</p>
        <p>&nbsp;</p>
        <p>&nbsp;</p>
        <p>&nbsp;</p>
        <p>&nbsp;</p>
        <p>&nbsp;</p>
        <p>&nbsp;</p>
        <p>&nbsp;</p>
        <p>&nbsp;</p>
        <p>&nbsp;</p>
        <p>&nbsp;</p>
        <p>&nbsp;</p>

      </div>
    </div>
    <q-page-scroller v-if="canReconcile" :offset="[16,16]" position="bottom-right" scroll-offset="120">
      <div class="bg-primary text-white q-px-xl q-pa-md rounded-borders">EUR {{ balanceDiff }}</div>
    </q-page-scroller>
  </q-page>
</template>

<script>

import startOfMonth from "date-fns/startOfMonth";
import endOfMonth from "date-fns/endOfMonth";
import subDays from 'date-fns/subDays';
import format from "date-fns/format";
import Get from "../../api/accounts/get";

export default {
  name: "Reconcile",
  data() {
    return {
      startDate: '',
      startBalance: '0',
      endDate: '',
      endBalance: '0',
      id: 0,
      canReconcile: true
    }
  },
  computed: {
    balanceDiff: function () {
      return parseFloat(this.startBalance) - parseFloat(this.endBalance);
    }
  },
  created() {
    this.id = parseInt(this.$route.params.id);
  },
  mounted() {
    this.setDates();
    this.collectBalances();
  },
  methods: {
    initReconciliation: function () {
      this.$q.dialog({
        title: 'Todo',
        message: 'This function does not work yet.',
        cancel: false,
        persistent: true
      });
    },
    setDates: function () {
      let today = new Date;
      // TODO depends on view range.
      let start = subDays(startOfMonth(today), 1);
      let end = endOfMonth(today);
      this.startDate = format(start, 'yyyy-MM-dd');
      this.endDate = format(end, 'yyyy-MM-dd');
    },
    collectBalances: function () {
      let getter = new Get;
      getter.get(this.id, this.startDate).then((response) => {
        if ('asset' !== response.data.data.attributes.type) {
          this.canReconcile = false;
        }
        this.startBalance = response.data.data.attributes.current_balance;
      });

      getter.get(this.id, this.endDate).then((response) => {
        this.endBalance = response.data.data.attributes.current_balance;
      });

    }
  }
}
</script>

<style scoped>

</style>
