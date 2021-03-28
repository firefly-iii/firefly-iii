<!--
  - MainCategoryList.vue
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
      <h3 class="card-title">{{ $t('firefly.categories') }}</h3>
    </div>
    <!-- body if loading -->
    <div v-if="loading && !error" class="card-body">
      <div class="text-center">
        <i class="fas fa-spinner fa-spin"></i>
      </div>
    </div>
    <!-- body if error -->
    <div v-if="error" class="card-body">
      <div class="text-center">
        <i class="fas fa-exclamation-triangle text-danger"></i>
      </div>
    </div>
    <!-- body if normal -->
    <div v-if="!loading && !error" class="card-body table-responsive p-0">
      <table class="table table-sm">
        <tbody>
        <tr v-for="category in sortedList">
          <td style="width:20%;">
            <a :href="'./categories/show/' + category.id">{{ category.name }}</a>
          </td>
          <td class="align-middle">
            <!-- SPENT -->
            <div v-if="category.spentPct > 0" class="progress">
              <div :aria-valuenow="category.spentPct" :style="{ width: category.spentPct  + '%'}" aria-valuemax="100"
                   aria-valuemin="0" class="progress-bar progress-bar-striped bg-danger"
                   role="progressbar">
                <span v-if="category.spentPct > 20">
                  {{ Intl.NumberFormat(locale, {style: 'currency', currency: category.currency_code}).format(category.spent) }}
                </span>
              </div>
              <span v-if="category.spentPct <= 20" class="progress-label" style="line-height: 16px;">&nbsp;
              {{ Intl.NumberFormat(locale, {style: 'currency', currency: category.currency_code}).format(category.spent) }}
              </span>

            </div>

            <!-- EARNED -->
            <div v-if="category.earnedPct > 0" class="progress justify-content-end" title="hello2">
              <span v-if="category.earnedPct <= 20" style="line-height: 16px;">
                {{ Intl.NumberFormat(locale, {style: 'currency', currency: category.currency_code}).format(category.earned) }}
                &nbsp;</span>
              <div :aria-valuenow="category.earnedPct" :style="{ width: category.earnedPct  + '%'}" aria-valuemax="100"
                   aria-valuemin="0" class="progress-bar progress-bar-striped bg-success"
                   role="progressbar" title="hello">
                <span v-if="category.earnedPct > 20">
                  {{ Intl.NumberFormat(locale, {style: 'currency', currency: category.currency_code}).format(category.earned) }}
                </span>
              </div>
            </div>

          </td>
        </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script>
import {createNamespacedHelpers} from "vuex";

const {mapState, mapGetters, mapActions, mapMutations} = createNamespacedHelpers('dashboard/index')

export default {
  name: "MainCategoryList",

  created() {
    this.locale = localStorage.locale ?? 'en-US';
    this.ready = true;
  },
  data() {
    return {
      locale: 'en-US',
      categories: [],
      sortedList: [],
      spent: 0,
      earned: 0,
      loading: true,
      error: false
    }
  },
  computed: {
    ...mapGetters(['start', 'end']),
    'datesReady': function () {
      return null !== this.start && null !== this.end && this.ready;
    }
  },
  watch: {
    datesReady: function (value) {
      if (true === value) {
        this.getCategories();
      }
    },
    start: function () {
      if (false === this.loading) {
        this.getCategories();
      }
    },
    end: function () {
      if (false === this.loading) {
        this.getCategories();
      }
    },
  },
  methods:
      {
        getCategories() {
          this.categories = [];
          this.sortedList = [];
          this.spent = 0;
          this.earned = 0;
          this.loading = true;
          let startStr = this.start.toISOString().split('T')[0];
          let endStr = this.end.toISOString().split('T')[0];
          axios.get('./api/v1/categories?start=' + startStr + '&end=' + endStr)
              .then(response => {
                      this.parseCategories(response.data);
                      this.loading = false;
                    }
              ).catch(error => {
            this.error = true;
          });
        },
        parseCategories(data) {
          for (let i in data.data) {
            if (data.data.hasOwnProperty(i) && /^0$|^[1-9]\d*$/.test(i) && i <= 4294967294) {
              let current = data.data[i];
              let entryKey = null;
              let categoryId = parseInt(current.id);

              // loop spent info:
              for (let ii in current.attributes.spent) {
                if (current.attributes.spent.hasOwnProperty(ii) && /^0$|^[1-9]\d*$/.test(ii) && ii <= 4294967294) {
                  let spentData = current.attributes.spent[ii];
                  entryKey = spentData.currency_id + '-' + current.id;

                  // does the categories list thing have this combo? if not, create it.
                  this.categories[entryKey] = this.categories[entryKey] ??
                      {
                        id: categoryId,
                        name: current.attributes.name,
                        currency_code: spentData.currency_code,
                        currency_symbol: spentData.currency_symbol,
                        spent: 0,
                        earned: 0,
                        spentPct: 0,
                        earnedPct: 0,
                      };
                  this.categories[entryKey].spent = parseFloat(spentData.sum);
                  this.spent = parseFloat(spentData.sum) < this.spent ? parseFloat(spentData.sum) : this.spent;
                }
              }

              // loop earned info
              for (let ii in current.attributes.earned) {
                if (current.attributes.earned.hasOwnProperty(ii) && /^0$|^[1-9]\d*$/.test(ii) && ii <= 4294967294) {
                  let earnedData = current.attributes.earned[ii];
                  entryKey = earnedData.currency_id + '-' + current.id;

                  // does the categories list thing have this combo? if not, create it.
                  this.categories[entryKey] = this.categories[entryKey] ??
                      {
                        id: categoryId,
                        name: current.attributes.name,
                        currency_code: earnedData.currency_code,
                        currency_symbol: earnedData.currency_symbol,
                        spent: 0,
                        earned: 0,
                        spentPct: 0,
                        earnedPct: 0,
                      };
                  this.categories[entryKey].earned = parseFloat(earnedData.sum);
                  this.earned = parseFloat(earnedData.sum) > this.earned ? parseFloat(earnedData.sum) : this.earned;
                }
              }
            }
          }
          this.sortCategories();
        },
        sortCategories() {
          // no longer care about keys:
          let array = [];
          for (let i in this.categories) {
            if (this.categories.hasOwnProperty(i)) {
              array.push(this.categories[i]);
            }
          }
          array.sort(function (one, two) {
            return (one.spent + one.earned) - (two.spent + two.earned);
          });
          for (let i in array) {
            if (array.hasOwnProperty(i)) {
              let current = array[i];
              current.spentPct = (current.spent / this.spent) * 100;
              current.earnedPct = (current.earned / this.earned) * 100;
              this.sortedList.push(current);
            }
          }
        }
      }
}
</script>

<style scoped>

</style>