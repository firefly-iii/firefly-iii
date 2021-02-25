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
  <div class="form-group" v-if="showField">
    <div class="text-xs d-none d-lg-block d-xl-block">
      {{ $t('firefly.internal_reference') }}
    </div>
    <div class="input-group">
      <input
          type="text"
          name="internal_reference[]"
          v-model="reference"
          :placeholder="$t('firefly.internal_reference')"
          :class="errors.length > 0 ? 'form-control is-invalid' : 'form-control'"
      />
      <div class="input-group-append">
        <button tabindex="-1" type="button" class="btn btn-outline-secondary"><i class="far fa-trash-alt"></i></button>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  props: ['index', 'value', 'errors', 'customFields'],
  name: "TransactionInternalReference",
  data() {
    return {
      reference: this.value,
      availableFields: this.customFields,
      emitEvent: true
    }
  },
  computed: {
    showField: function () {
      if ('internal_reference' in this.availableFields) {
        return this.availableFields.internal_reference;
      }
      return false;
    }
  },
  methods: {
  },
  watch: {
    customFields: function (value) {
      this.availableFields = value;
    },
    value: function (value) {
      this.emitEvent = false;
      this.reference = value;
    },
    reference: function (value) {
      this.$emit('set-field', {field: 'internal_reference', index: this.index, value: value});
    }
  }
}
</script>
