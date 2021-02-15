<!--
  - TransactionLocation.vue
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
      {{ $t('firefly.location') }}
    </div>
    <div class="input-group">
      (TODO)
    </div>
  </div>
</template>

<script>
export default {
  name: "TransactionLocation",
  props: ['index', 'value', 'errors', 'customFields'],
  data() {
    return {
      availableFields: this.customFields
    }
  },
  computed: {
    showField: function () {
      if ('location' in this.availableFields) {
        return this.availableFields.location;
      }
      return false;
    }
  },
  watch: {
    customFields: function (value) {
      this.availableFields = value;
    },
  }
}
</script>

<style scoped>

</style>