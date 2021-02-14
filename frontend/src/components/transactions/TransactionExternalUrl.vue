<!--
  - TransactionInternalReference.vue
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
  <div class="form-group">
    <div class="text-xs d-none d-lg-block d-xl-block">
      {{ $t('firefly.external_url') }}
    </div>
    <div class="input-group">
      <input
          type="url"
          name="external_url[]"
          :placeholder="$t('firefly.external_url')"
          v-model="url"
          :class="errors.length > 0 ? 'form-control is-invalid' : 'form-control'"
      />
      <div class="input-group-append">
        <button tabindex="-1" type="button" class="btn btn-outline-secondary"><i class="far fa-trash-alt"></i></button>
      </div>
    </div>
  </div>
</template>

<script>
import {createNamespacedHelpers} from "vuex";

const {mapState, mapGetters, mapActions, mapMutations} = createNamespacedHelpers('transactions/create')

export default {
  props: ['index', 'value', 'errors'],
  name: "TransactionExternalUrl",
  data() {
    return {
      url: this.value
    }
  },
  methods: {
    ...mapMutations(
        [
          'updateField',
        ],
    ),
  },
  watch: {
    url: function (value) {
      this.updateField({field: 'external_url', index: this.index, value: value});
    }
  }
}
</script>

<style scoped>

</style>