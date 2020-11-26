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
    <div class="card-body table-responsive p-0">
      <table class="table table-sm">
        <tbody>
        <tr v-for="category in sortedList">
          <td style="width:20%;">
            <a :href="'./categories/show/' + category.id">{{ category.name }}</a>
            <!--<p>Spent: {{ category.spentPct }}</p>
            <p>earned: {{ category.earnedPct }}</p>
            -->
          </td>
          <td class="align-middle">
            <!-- SPENT -->
            <div class="progress" v-if="category.spentPct > 0">
              <div class="progress-bar progress-bar-striped bg-danger" role="progressbar" :aria-valuenow="category.spentPct"
                   :style="{ width: category.spentPct  + '%'}" aria-valuemin="0"
                   aria-valuemax="100">
                <span v-if="category.spentPct > 20">
                  {{ Intl.NumberFormat(locale, {style: 'currency', currency: category.currency_code}).format(category.spent) }}
                </span>
              </div>
              <span v-if="category.spentPct <= 20">&nbsp;
              {{ Intl.NumberFormat(locale, {style: 'currency', currency: category.currency_code}).format(category.spent) }}
              </span>
            </div>

            <!-- EARNED -->
            <div class="progress justify-content-end" v-if="category.earnedPct > 0" title="hello2">
              <span v-if="category.earnedPct <= 20">
                {{ Intl.NumberFormat(locale, {style: 'currency', currency: category.currency_code}).format(category.earned) }}
                &nbsp;</span>
              <div class="progress-bar progress-bar-striped bg-success" role="progressbar" :aria-valuenow="category.earnedPct" :style="{ width: category.earnedPct  + '%'}" aria-valuemin="0"
                   aria-valuemax="100" title="hello">
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
export default {
  name: "MainCategoryList",

  mounted() {
    this.locale = localStorage.locale ?? 'en-US';
    this.getCategories();
  },
  data() {
    return {
      locale: 'en-US',
      categories: [],
      sortedList: [],
      spent: 0,
      earned: 0
    }
  },
  methods:
      {
        getCategories() {
          axios.get('./api/v1/categories?start=' + window.sessionStart + '&end=' + window.sessionEnd)
              .then(response => {
                      this.parseCategories(response.data);
                    }
              );
        },
        parseCategories(data) {
          for (let key in data.data) {
            if (data.data.hasOwnProperty(key) && /^0$|^[1-9]\d*$/.test(key) && key <= 4294967294) {
              let current = data.data[key];
              let entryKey = null;
              let categoryId = parseInt(current.id);

              // loop spent info:
              for (let subKey in current.attributes.spent) {
                if (current.attributes.spent.hasOwnProperty(subKey) && /^0$|^[1-9]\d*$/.test(subKey) && subKey <= 4294967294) {
                  let spentData = current.attributes.spent[subKey];
                  entryKey = spentData.currency_id.toString() + '-' + current.id.toString();

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
              for (let subKey in current.attributes.earned) {
                if (current.attributes.earned.hasOwnProperty(subKey) && /^0$|^[1-9]\d*$/.test(subKey) && subKey <= 4294967294) {
                  let earnedData = current.attributes.earned[subKey];
                  entryKey = earnedData.currency_id.toString() + '-' + current.id.toString();

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
          for (let cat in this.categories) {
            if (this.categories.hasOwnProperty(cat)) {
              array.push(this.categories[cat]);
            }
          }
          array.sort(function (one, two) {
            return (one.spent + one.earned) - (two.spent + two.earned);
          });
          for (let cat in array) {
            if (array.hasOwnProperty(cat)) {
              let current = array[cat];
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