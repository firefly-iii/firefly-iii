<!--
  - IndexOptions.vue
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
    <p v-if="'asset' === type">
      <input type="checkbox" name="order_mode" id="order_mode" v-model="orderMode"> <label for="order_mode">Enable order mode</label>
    </p>
  </div>
</template>

<script>

export default {
  name: "IndexOptions",
  data() {
    return {
      type: 'invalid'
    }
  },
  computed: {
    orderMode: {
      get() {
        return this.$store.getters["accounts/index/orderMode"];
      },
      set(value) {
        this.$store.commit('accounts/index/setOrderMode', value);
      }
    },

  },
  created() {
    let pathName = window.location.pathname;
    let parts = pathName.split('/');
    this.type = parts[parts.length - 1];
  }
}
</script>

<style scoped>

</style>