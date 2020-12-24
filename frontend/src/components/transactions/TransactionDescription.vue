<!--
  - TransactionDescription.vue
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
    <div class="col">
      <div class="input-group">
        <input
            ref="description"
            :title="$t('firefly.description')"
            :value="value"
            autocomplete="off"
            autofocus
            class="form-control"
            name="description[]"
            type="text"
            :placeholder="$t('firefly.description')"
            v-on:submit.prevent
        >
        <div class="input-group-append">
          <!-- v-on:click="clearDescription" -->
          <button class="btn btn-outline-secondary" type="button"><i class="far fa-trash-alt"></i></button>
        </div>
      </div>
    </div>
  </div>

</template>

<script>
export default {
  props: ['value'],
  name: "TransactionDescription"
}
</script>

<style scoped>

</style>