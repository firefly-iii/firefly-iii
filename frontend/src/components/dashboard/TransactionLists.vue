<!--
  - TransactionLists.vue
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
  <div class="row">
    <div v-for="(account) in accounts" class="col q-mr-sm">
      <TransactionList :account-id="account"/>
    </div>
  </div>
</template>

<script>
import Preferences from "../../api/v2/preferences";
import {defineAsyncComponent} from "vue";

export default {
  name: "TransactionLists",
  components: {
    TransactionList: defineAsyncComponent(() => import('./TransactionList.vue')),
  },
  data() {
    return {
      accounts: [],
    }
  },
  mounted() {
    this.getAccounts();
  },
  methods: {
    getAccounts: function () {
      (new Preferences).get('frontpageAccounts').then((response) => this.parseAccounts(response.data));
    },
    parseAccounts: function (data) {
      const content = data.data.attributes.data;
      for (let i in content) {
        if (content.hasOwnProperty(i)) {
          this.accounts.push(content[i]);
        }
      }
    }
  },
}
</script>
