<!--
  - Index.vue
  - Copyright (c) 2018 thegrumpydictator@gmail.com
  -
  - This file is part of Firefly III.
  -
  - Firefly III is free software: you can redistribute it and/or modify
  - it under the terms of the GNU General Public License as published by
  - the Free Software Foundation, either version 3 of the License, or
  - (at your option) any later version.
  -
  - Firefly III is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU General Public License for more details.
  -
  - You should have received a copy of the GNU General Public License
  - along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
  -->
<template>
    <div>
        <table class="table table-hover sortable">
            <thead>
            <tr>
                <th class="hidden-sm hidden-xs" data-defaultsort="disabled">&nbsp;</th>
                <th>{{ 'list.name' | trans }}</th>
                <th data-defaultsign="az" class="hidden-sm hidden-md hidden-xs">{{ 'list.matchesOn' | trans }}</th>
                <th data-defaultsign="_19" colspan="2">{{ 'list.amount' | trans }}</th>
                <th data-defaultsign="month" class="hidden-sm hidden-xs">{{ 'list.paid_current_period' | trans }}</th>
                <th data-defaultsign="month" class="hidden-sm hidden-xs">{{ 'list.next_expected_match' | trans }}</th>
                <th class="hidden-sm hidden-xs hidden-md">{{ 'list.active' | trans }}</th>
                <th class="hidden-sm hidden-xs hidden-md">{{ 'list.automatch' | trans }}</th>
                <th data-defaultsign="az" class="hidden-sm hidden-xs">{{ 'list.repeat_freq' | trans }}</th>
            </tr>
            </thead>
            <tbody>
            <tr v-for="(bill, index) in list">
                <td class="hidden-sm hidden-xs">
                    <div class="btn-group btn-group-xs edit_tr_buttons"><a href="x" class="btn btn-default btn-xs"><i class="fa fa-fw fa-pencil"></i></a><a href="x" class="btn btn-danger btn-xs"><i class="fa fa-fw fa-trash-o"></i></a></div>
                </td>
                <td :data-value="bill.attributes.name">
                    <a href="x" :title="bill.attributes.name">{{ bill.attributes.name }}</a>
                    <i v-if='bill.attributes.attachments_count > 0' class="fa fa-paperclip"></i>
                </td>
                <td class="hidden-sm hidden-md hidden-xs">
                    <span v-for="(word) in bill.attributes.match"><span class="label label-info">{{ word }}</span>&nbsp;</span>
                </td>
                <td :data-value="bill.attributes.amount_min" style="text-align: right;">
                <span style="margin-right:5px;" v-html="formatAmount(bill.attributes.amount_min)"></span>
                </td>
                <td :data-value="bill.attributes.amount_max" style="text-align: right;">
                    <span style="margin-right:5px;" v-html="formatAmount(bill.attributes.amount_max)"></span>
                </td>
                <!-- first two -->
                <td v-if="bill.attributes.paid_dates.length == 0 && bill.attributes.pay_dates.length == 0 && bill.attributes.active" class="paid_in_period text-muted">
                    {{ 'components.not_expected_period' | trans }}
                </td>
                <td v-if="bill.attributes.paid_dates.length == 0 && bill.attributes.pay_dates.length == 0 && bill.attributes.active" class="expected_in_period hidden-sm hidden-xs">
                    {{ bill.attributes.next_expected_match|formatDate }}
                </td>

                <!-- second set -->
                <td v-if="bill.attributes.paid_dates.length == 0 && bill.attributes.pay_dates.length > 0 && bill.attributes.active" class="paid_in_period text-danger">
                    {{ 'components.not_or_not_yet' | trans }}
                </td>
                <td v-if="bill.attributes.paid_dates.length == 0 && bill.attributes.pay_dates.length > 0 && bill.attributes.active" class="expected_in_period hidden-sm hidden-xs">
                    {{ bill.attributes.next_expected_match|formatDate }}
                </td>

                <!-- third set -->
                <td v-if="bill.attributes.paid_dates.length > 0 && bill.attributes.active" class="paid_in_period text-success">
                    <span v-for="date in bill.attributes.paid_dates">{{ date|formatDate }}<br /></span>
                </td>
                <td v-if="bill.attributes.paid_dates.length > 0 && bill.attributes.active" class="expected_in_period hidden-sm hidden-xs">
                    {{ bill.attributes.next_expected_match|formatDate }}
                </td>

                <!-- last set -->
                <td v-if="bill.attributes.active === false" class="paid_in_period text-muted" data-value="0000-00-00 00-00-00">
                    ~
                </td>
                <td v-if="bill.attributes.active === false" class="expected_in_period text-muted hidden-sm hidden-xs" data-value="0">
                    ~
                </td>
                <td class="hidden-sm hidden-xs hidden-md" :data-value="bill.attributes.active">
                    <i v-if="bill.attributes.active === true" class="fa fa-fw fa-check"></i>
                    <i v-if="bill.attributes.active === false" class="fa fa-fw fa-ban"></i>
                </td>
                <td class="hidden-sm hidden-xs hidden-md" :data-value="bill.attributes.automatch">
                    <i v-if="bill.attributes.automatch === true" class="fa fa-fw fa-check"></i>
                    <i v-if="bill.attributes.automatch === false" class="fa fa-fw fa-ban"></i>
                </td>
                <td class="hidden-sm hidden-xs" :data-value="bill.attributes.repeat_freq + bill.attributes.skip">
                    {{ bill.attributes.repeat_freq }}
                    <span v-if="bill.attributes.skip > 0">Skips over {{ bill.attributes.skip }}</span>
                </td>
            </tr>
            <!--<button @click="deleteBill(bill.attributes.id)" class="btn btn-danger btn-xs pull-right">Delete</button>-->
            </tbody>
        </table>
    </div>
</template>

<script>
    export default {
        data() {
            return {
                list: [],
                bill: {
                    id: '',
                    name: ''
                }
            };
        },

        created() {
            this.fetchBillList();
        },

        methods: {
            formatAmount:  Vue.filter('formatAmount'),
            trans: Vue.filter('trans'),
            fetchBillList() {
                axios.get('api/v1/bill', {params: {start: window.sessionStart, end: window.sessionEnd}}).then((res) => {
                    this.list = res.data.data;
                });
            },

            deleteBill(id) {
                axios.delete('api/bills/' + id)
                    .then((res) => {
                        this.fetchBillList()
                    })
                    .catch((err) => console.error(err));
            },
        }
    }
</script>
