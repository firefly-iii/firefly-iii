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
  <div v-if="showField" class="form-group">
    <div class="text-xs d-none d-lg-block d-xl-block">
      {{ $t('firefly.external_url') }}
    </div>
    <div class="input-group">
      <input
          v-model="url"
          :class="errors.length > 0 ? 'form-control is-invalid' : 'form-control'"
          :placeholder="$t('firefly.external_url')"
          name="external_url[]"
          type="url"
      />
      <div class="input-group-append">
        <button class="btn btn-outline-secondary" tabindex="-1" type="button"><i class="far fa-trash-alt"></i></button>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  props: ['index', 'value', 'errors', 'customFields'],
  name: "TransactionExternalUrl",
  data() {
    return {
      url: this.value,
      availableFields: this.customFields,
    }
  },
  computed: {
    showField: function () {
      if ('external_uri' in this.availableFields) {
        return this.availableFields.external_uri;
      }
      return false;
    }
  },
  methods: {},
  watch: {
    customFields: function (value) {
      this.availableFields = value;
    },
    value: function (value) {
      this.url = value;
    },
    url: function (value) {
      this.$emit('set-field', {field: 'external_url', index: this.index, value: value});
    }
  }
}
</script>

<style scoped>

</style>