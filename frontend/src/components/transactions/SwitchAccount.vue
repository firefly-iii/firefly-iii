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
      <span class="text-muted" v-if="'any' !== this.transactionType">
        {{ $t('firefly.' + this.transactionType) }}
      </span>
      <span class="text-muted" v-if="'any' === this.transactionType">&nbsp;</span>
    </div>
    <div class="btn-group d-flex">
      <button class="btn btn-light" @click="switchAccounts">&harr;</button>
    </div>
  </div>
</template>

<script>

import {createNamespacedHelpers} from "vuex";

const {mapState, mapGetters, mapActions, mapMutations} = createNamespacedHelpers('transactions/create')

export default {
  name: "SwitchAccount",
  props: ['index'],
  methods: {
    ...mapMutations(
        [
          'updateField',
        ],
    ),

    switchAccounts() {
      let source = this.transactions[this.index].source_account;
      let dest = this.transactions[this.index].destination_account;

      this.updateField({field: 'source_account', index: this.index, value: dest});
      this.updateField({field: 'destination_account', index: this.index, value: source});

      // trigger other components.

    }
  },
  computed: {
    ...mapGetters(['transactions', 'transactionType']),
  }
}
</script>

<style scoped>

</style>