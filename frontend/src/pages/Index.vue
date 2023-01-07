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
    <div v-if="0 === assetCount">
      <NewUser
        v-on:created-accounts="refreshThenCount"
      ></NewUser>
    </div>
    <div v-if="assetCount > 0">
      <Dashboard/>
    </div>
  </q-page>
</template>

<script>

import {defineAsyncComponent, defineComponent} from "vue";
import List from "../api/accounts/list";
//import {mapGetters} from "vuex";
import {useFireflyIIIStore} from '../stores/fireflyiii'
import Dashboard from "./dashboard/Dashboard";

export default defineComponent(
  {
    name: 'PageIndex',
    components: {
      Dashboard,
      NewUser: defineAsyncComponent(() => import('../components/dashboard/NewUser')),
    },
    data() {
      return {
        assetCount: 1,
        $store: null
      }
    },
    mounted() {
      this.countAssetAccounts();
    },
    methods: {
      refreshThenCount: function () {
        this.$store = useFireflyIIIStore();
        this.$store.refreshCacheKey();
        this.countAssetAccounts();
      },
      countAssetAccounts: function () {
        let list = new List;
        list.list('asset', 1, this.getCacheKey).then((response) => {
          this.assetCount = parseInt(response.data.meta.pagination.total);
        });
      }
    }
  })
</script>
