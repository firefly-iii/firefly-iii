<!--
  - LiabilityDate.vue
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
      {{ $t('form.date') }}
    </div>
    <div class="input-group">
      <input
          v-model="date"
          :class="errors.length > 0 ? 'form-control is-invalid' : 'form-control'"
          :placeholder="$t('form.date')"
          name="liability_date"
          :disabled=disabled
          type="date"
      />
      <div class="input-group-append">
        <button class="btn btn-outline-secondary" tabindex="-1" type="button"><i class="far fa-trash-alt"></i></button>
      </div>
    </div>
    <span v-if="errors.length > 0">
      <span v-for="error in errors" class="text-danger small">{{ error }}<br/></span>
    </span>
  </div>
</template>

<script>
export default {
  name: "LiabilityDate",
  props: {
    value: {},
    errors: {},
    disabled: {
      type: Boolean,
      default: false
    },
  },
  data() {
    return {
      date: this.value
    }
  },
  watch: {
    date: function (value) {
      this.$emit('set-field', {field: 'liability_date', value: value});
    },
  }
}
</script>

<style scoped>

</style>