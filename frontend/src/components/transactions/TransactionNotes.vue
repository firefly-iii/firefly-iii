<!--
  - TransactionNotes.vue
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
      {{ $t('firefly.notes') }}
    </div>
    <div class="input-group">
      <textarea
          v-model="notes"
          :class="errors.length > 0 ? 'form-control is-invalid' : 'form-control'"
          :placeholder="$t('firefly.notes')"
      ></textarea>
    </div>
  </div>

</template>

<script>
export default {
  props: ['index', 'value', 'errors', 'customFields'],
  name: "TransactionNotes",
  data() {
    return {
      notes: this.value,
      availableFields: this.customFields,
    }
  },
  computed: {
    showField: function () {
      if ('notes' in this.availableFields) {
        return this.availableFields.notes;
      }
      return false;
    }
  },
  watch: {
    value: function (value) {
      this.notes = value;
    },
    customFields: function (value) {
      this.availableFields = value;
    },
    notes: function (value) {
      this.$emit('set-field', {field: 'notes', index: this.index, value: value});
    }
  }
}
</script>

<style scoped>

</style>