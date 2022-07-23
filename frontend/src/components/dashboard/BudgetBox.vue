<!--
  - BudgetBox.vue
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
  <div class="q-mt-sm q-mr-sm">
    <q-card bordered>
      <q-item>
        <q-item-section>
          <q-item-label><strong>
            Budgets
          </strong></q-item-label>
        </q-item-section>
      </q-item>
      <q-separator/>
      <q-card-section>
        <div class="row">
          <div class="col">
            I am budget<br/>
          </div>
        </div>
        <div class="row">
          <div class="col">
            <small>I am range</small>
          </div>
          <div class="col">
            I am bar
          </div>
        </div>
        <div class="row">
          <div class="col">
            <small>I am range</small>
          </div>
          <div class="col">
            I am bar
          </div>
        </div>
        <div class="row">
          <div class="col">
            I am budget<br/>
          </div>
        </div>
      </q-card-section>

    </q-card>
  </div>
</template>

<script>
import {useFireflyIIIStore} from "../../stores/fireflyiii";
import List from '../../api/v2/budgets/list';

export default {
  name: "BudgetBox",
  data() {
    return {
      budgets: [],
      locale: 'en-US',
      page: 1
    }
  },
  mounted() {
    this.store = useFireflyIIIStore();
    this.store.$onAction(
      ({name, store, args, after, onError,}) => {
        after((result) => {
          if (name === 'setRange') {
            this.locale = this.store.getLocale;
            this.loadBox();
          }
        })
      }
    )
    if (null !== this.store.getRange.start && null !== this.store.getRange.end) {
      this.loadBox();
    }
  },
  methods: {
    loadBox: function() {

      (new List).list(1).then((data) => {
        console.log(data.data);
      });
      // todo go to next page as well.

      console.log('loadbox');
    }
  }
}
</script>

<style scoped>

</style>
