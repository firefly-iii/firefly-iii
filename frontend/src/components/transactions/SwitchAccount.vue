<!--
  - SwitchAccount.vue
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
  <div class="form-group">
    <div class="text-xs d-none d-lg-block d-xl-block">
      <span v-if="'any' !== this.transactionType" class="text-muted">
        {{ $t('firefly.' + this.transactionType) }}
      </span>
      <span v-if="'any' === this.transactionType" class="text-muted">&nbsp;</span>
    </div>
    <!--
    <div class="btn-group d-flex">
      <button class="btn btn-light" @click="switchAccounts">&harr;</button>
    </div>
    -->
  </div>
</template>

<script>
export default {
  name: "SwitchAccount",
  props: ['index', 'transactionType'],
  methods: {
    // switchAccounts() {
    //   this.$emit('switch-accounts', this.index);
    // }
  }
}
</script>

<style scoped>

</style>