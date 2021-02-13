<!--
  - MainBills.vue
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
  <div class="card">
    <div class="card-header">
      <h3 class="card-title">{{ $t('firefly.bills') }}</h3>
    </div>
    <div class="card-body table-responsive p-0">
      <table class="table table-striped">
        <caption style="display:none;">{{ $t('firefly.bills') }}</caption>
        <thead>
        <tr>
          <th scope="col" style="width:35%;">{{ $t('list.name') }}</th>
          <th scope="col" style="width:25%;">{{ $t('list.next_expected_match') }}</th>
        </tr>
        </thead>
        <tbody>
        <tr v-for="bill in this.bills">
          <td><a :href="'./bills/show/' + bill.id" :title="bill.attributes.name">{{ bill.attributes.name }}</a>
            (~ <span class="text-danger">{{
                Intl.NumberFormat(locale, {style: 'currency', currency: bill.attributes.currency_code}).format((parseFloat(bill.attributes.amount_min) +
                                                                                                                parseFloat(bill.attributes.amount_max)) / -2)
              }}</span>)
            <small v-if="bill.attributes.object_group_title" class="text-muted">
              <br/>
              {{ bill.attributes.object_group_title }}
            </small>
          </td>
          <td>
            <span v-for="paidDate in bill.attributes.paid_dates">
              <span v-html="renderPaidDate(paidDate)"/><br/>
            </span>
            <span v-for="payDate in bill.attributes.pay_dates" v-if="0===bill.attributes.paid_dates.length">
              {{ new Intl.DateTimeFormat(locale, {year: 'numeric', month: 'long', day: 'numeric'}).format(new Date(payDate)) }}<br/>
            </span>
          </td>
        </tr>
        </tbody>
      </table>
    </div>
    <div class="card-footer">
      <a href="./bills" class="btn btn-default button-sm"><i class="far fa-money-bill-alt"></i> {{ $t('firefly.go_to_bills') }}</a>
    </div>
  </div>
</template>
<script>
import {createNamespacedHelpers} from "vuex";

const {mapState, mapGetters, mapActions, mapMutations} = createNamespacedHelpers('dashboard/index')
export default {
  name: "MainBillsList",
  computed: {
    ...mapGetters([
                    'start',
                    'end'
                  ]),
    'datesReady': function () {
      return null !== this.start && null !== this.end && this.ready;
    }
  },
  watch: {
    datesReady: function (value) {
      if (true === value) {
        // console.log(this.chartOptions);
        this.initialiseBills();
      }
    }
  },
  created() {
    this.ready = true;
    this.locale = localStorage.locale ?? 'en-US';
  },
  components: {},
  methods: {
    initialiseBills: function () {
      let startStr = this.start.toISOString().split('T')[0];
      let endStr = this.end.toISOString().split('T')[0];

      axios.get('./api/v1/bills?start=' + startStr + '&end=' + endStr)
          .then(response => {
                  this.loadBills(response.data.data);
                }
          );
    },
    renderPaidDate: function (obj) {
      let dateStr = new Intl.DateTimeFormat(this.locale, {year: 'numeric', month: 'long', day: 'numeric'}).format(new Date(obj.date));
      let str = this.$t('firefly.bill_paid_on', {date: dateStr});
      return '<a href="./transactions/show/' + obj.transaction_group_id + '" title="' + str + '">' + str + '</a>';
    },
    loadBills: function (data) {
      for (let key in data) {
        if (data.hasOwnProperty(key) && /^0$|^[1-9]\d*$/.test(key) && key <= 4294967294) {

          let bill = data[key];
          let active = bill.attributes.active;
          if (bill.attributes.pay_dates.length > 0 && active) {
            this.bills.push(bill);
          }
        }
      }
    }
  },
  data() {
    return {
      bills: [],
      locale: 'en-US',
      ready: false
    }
  },
}
</script>
