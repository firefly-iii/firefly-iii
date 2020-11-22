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
                    <th scope="col" style="width:40%;">{{ $t('list.amount') }}</th>
                    <th scope="col" style="width:25%;">{{ $t('list.next_expected_match') }}</th>
                </tr>
                </thead>
                <tbody>
                <tr v-for="bill in this.bills">
                    <td><a :href="'./bills/show' + bill.id" :title="bill.attributes.name">{{ bill.attributes.name }}</a></td>
                    <td>~{{ Intl.NumberFormat('en-US', {style: 'currency', currency: bill.attributes.currency_code}).format((bill.attributes.amount_min +
                        bill.attributes.amount_max) / 2) }}
                    </td>
                    <td>
                        <span v-for="payDate in bill.attributes.pay_dates">
                            {{ payDate }}<br />
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

    export default {
        name: "MainBillsList",
      created() {
            axios.get('./api/v1/bills?start=' + window.sessionStart + '&end=' + window.sessionEnd)
                .then(response => {
                          this.loadBills(response.data.data);
                      }
                );
        },
        components: {},
        methods: {
            loadBills(data) {
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
                bills: []
            }
        },
        computed: {},
    }
</script>
