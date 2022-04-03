<!--
  - Index.vue
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
    <div class="row q-mx-md">
      <div class="col-4">
        <q-card bordered>
          <q-card-section>
            <div class="text-h6">Reports</div>
          </q-card-section>
          <q-card-section>
            <q-select
              bottom-slots
              outlined
              v-model="type"
              emit-value class="q-pr-xs"
              map-options :options="types" label="Report type"/>

            <q-select
              bottom-slots
              outlined
              :disable="loading"
              v-model="selectedAccounts"
              class="q-pr-xs"
              multiple
              emit-value
              use-chips
              map-options :options="accounts" label="Included accounts"/>
            <q-input
              bottom-slots
              type="date" v-model="start_date" :label="$t('form.start_date')"
              hint="Start date"
              outlined/>
            <q-input
              bottom-slots
              type="date" v-model="end_date" :label="$t('form.start_date')"
              hint="Start date"
              outlined/>
          </q-card-section>
          <q-card-actions>
            <q-btn :disable="loading || selectedAccounts.length < 1" @click="submit" color="primary" label="View report"/>
          </q-card-actions>
        </q-card>
      </div>
    </div>
  </q-page>
</template>

<script>
import List from "../../api/accounts/list";
import {startOfMonth} from "date-fns";
import {format} from "date-fns";
import {endOfMonth} from "date-fns";
export default {
  name: 'Index',
  created() {
    this.getAccounts();
    this.start_date = format(startOfMonth(new Date), 'yyyy-MM-dd');
    this.end_date = format(endOfMonth(new Date), 'yyyy-MM-dd');
  },
  data() {
    return {
      // is loading:
      loading: false,

      // report settings
      type: 'default',
      selectedAccounts: [],
      accounts: [],
      start_date: '',
      end_date: '',

      types: [
        {value: 'default', label: 'Default financial report'},
        // value="audit">Transaction history overview (audit)
        // value="budget">Budget report</option>
        // value="category">Category report</option>
        // value="tag">Tag report</option>
        // value="double">Expense/revenue account report</option> // to be dropped
      ],
    }
  },
  methods: {
    submit: function() {
      let start = this.start_date.replace('-','');
      let end = this.end_date.replace('-','');
      let accounts = this.selectedAccounts.join(',');
      if('default' === this.type) {
        this.$router.push(
          {name: 'reports.default',
            params:
              {
                accounts: accounts,
                start: start,
                end: end
              }
          }
          );
      }
    },
    // duplicate function
    getAccounts: function () {
      this.loading = true;
      this.getPage(1);
    },
    // duplicate function
    getPage: function (page) {
      (new List).list('all', page, this.getCacheKey).then((response) => {
        let totalPages = parseInt(response.data.meta.pagination.total_pages);

        // parse these accounts:
        for (let i in response.data.data) {
          if (response.data.data.hasOwnProperty(i)) {
            let account = response.data.data[i];
            if ('liabilities' === account.attributes.type || 'asset' === account.attributes.type) {
              this.accounts.push(
                {
                  value: parseInt(account.id),
                  label: account.attributes.type + ': ' + account.attributes.name,
                  decimal_places: parseInt(account.attributes.currency_decimal_places)
                }
              );
            }
          }
        }

        if (page < totalPages) {
          this.getPage(page + 1);
        }
        if (page === totalPages) {
          this.loading = false;
          this.accounts.sort((a, b) => (a.label > b.label) ? 1 : ((b.label > a.label) ? -1 : 0))
        }
      });
    },
  }
}
</script>
