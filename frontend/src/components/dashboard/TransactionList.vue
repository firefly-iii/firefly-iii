<!--
  - TransactionList.vue
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
  <div>
    <q-card bordered>
      <q-item>
        <q-item-section>
          <q-item-label><strong>{{ accountName }}</strong></q-item-label>
        </q-item-section>
      </q-item>
      <q-separator/>
      <q-item>
        <q-card-section horizontal>
          Content
        </q-card-section>
      </q-item>


      <!-- X:  {{ accountId }} -->
    </q-card>
  </div>
</template>

<script>
import Get from "../../api/v2/accounts/get";
import {useFireflyIIIStore} from "../../stores/fireflyiii";
import {format} from "date-fns";

export default {
  name: "TransactionList",
  props: {
    accountId: 0,
  },
  data() {
    return {
      store: null,
      accountName: ''
    }
  },
  mounted() {
    this.store = useFireflyIIIStore();
    if (0 !== this.accountId) {
      this.getAccount();
      // TODO this code snippet is recycled a lot.
      // subscribe, then update:
      this.store.$onAction(
        ({name, $store, args, after, onError,}) => {
          after((result) => {
            if (name === 'setRange') {
              this.getTransactions();
            }
          })
        }
      )
      this.getTransactions();
    }
  },
  methods: {
    getAccount: function () {
      (new Get).get(this.accountId).then((response) => this.parseAccount(response.data));
    },
    parseAccount: function (data) {
      this.accountName = data.data.attributes.name;
    },
    getTransactions: function () {
      if (null !== this.store.getRange.start && null !== this.store.getRange.end) {
        const start = new Date(this.store.getRange.start);
        const end = new Date(this.store.getRange.end);
        let startStr = format(start, 'y-MM-dd');
        let endStr = format(end, 'y-MM-dd');
        (new Get).transactions(this.accountId, {
          start: startStr,
          end: endStr
        }).then((response) => this.parseTransactions(response.data));
      }
    },
    parseTransactions: function () {

    }
  },

}
</script>

<style scoped>

</style>
