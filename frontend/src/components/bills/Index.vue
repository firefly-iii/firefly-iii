<!--
  - Index.vue
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
  <div>
    <div class="row" v-for="group in sortedGroups">
      <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
        <div class="card">
          <div class="card-header">
            <h3 class="card-title">
              {{ group[1].title }}
            </h3>
          </div>
          <div class="card-body p-0">
            <b-table id="my-table" striped hover responsive="md" primary-key="id" :no-local-sorting="false"
                     :items="group[1].bills"
                     sort-icon-left
                     :busy.sync="loading"
            >
            </b-table>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import {mapGetters, mapMutations} from "vuex";
import {configureAxios} from "../../shared/forageStore";

export default {
  name: "Index",
  data() {
    return {
      groups: {},
      downloaded: false,
      loading: false,
      locale: 'en-US',
      sortedGroups: [],
    }
  },
  computed: {
    ...mapGetters('root', ['cacheKey']),
  },
  created() {
    this.locale = localStorage.locale ?? 'en-US';
    this.downloadBills(1);
  },
  methods: {
    ...mapMutations('root', ['refreshCacheKey',]),
    resetGroups: function () {
      this.groups = {};
      this.groups[0] =
          {
            id: 0,
            title: this.$t('firefly.default_group_title_name'),
            order: 1,
            bills: []
          };
    },
    downloadBills: function (page) {
      this.resetGroups();
      configureAxios().then(async (api) => {
        api.get('./api/v1/bills?page=' + page + 'key=' + this.cacheKey)
            .then(response => {
                    // pages
                    let currentPage = parseInt(response.data.meta.pagination.current_page);
                    let totalPage = parseInt(response.data.meta.pagination.total_pages);
                    this.parseBills(response.data.data);
                    if (currentPage < totalPage) {
                      let nextPage = currentPage + 1;
                      this.downloadBills(nextPage);
                    }
                    if (currentPage >= totalPage) {
                      this.downloaded = true;
                    }
                    this.sortGroups();
                  }
            );
      });
    },
    sortGroups: function () {
      const sortable = Object.entries(this.groups);
      //console.log('sortable');
      //console.log(sortable);
      sortable.sort(function (a, b) {
        return a.order - b.order;
      });
      this.sortedGroups = sortable;
      //console.log(this.sortedGroups);
    },
    parseBills: function (data) {
      for (let key in data) {
        if (data.hasOwnProperty(key) && /^0$|^[1-9]\d*$/.test(key) && key <= 4294967294) {
          let current = data[key];
          let bill = {};

          // create group of necessary.
          let groupId = null === current.attributes.object_group_id ? 0 : parseInt(current.attributes.object_group_id);
          if (0 !== groupId && !(groupId in this.groups)) {
            this.groups[groupId] = {
              id: groupId,
              title: current.attributes.object_group_title,
              order: parseInt(current.attributes.object_group_order),
              bills: []
            }
          }

          bill.id = parseInt(current.id);
          bill.order = parseInt(current.order);
          bill.name = current.attributes.name;
          bill.repeat_freq = current.attributes.repeat_freq;
          bill.skip = current.attributes.skip;
          bill.active = current.attributes.active;
          bill.amount_max = parseFloat(current.attributes.amount_max);
          bill.amount_min = parseFloat(current.attributes.amount_min);
          bill.currency_code = parseFloat(current.attributes.currency_code);
          bill.currency_id = parseFloat(current.attributes.currency_id);
          bill.currency_decimal_places = parseFloat(current.attributes.currency_decimal_places);
          bill.currency_symbol = parseFloat(current.attributes.currency_symbol);
          bill.next_expected_match = parseFloat(current.attributes.next_expected_match);
          bill.notes = parseFloat(current.attributes.notes);
          bill.paid_dates = parseFloat(current.attributes.paid_dates);
          bill.pay_dates = parseFloat(current.attributes.pay_dates);

          this.groups[groupId].bills.push(bill);
        }
      }
    }
  }
}
</script>