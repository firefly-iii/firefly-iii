<!--
  - Alert.vue
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
  <div :class="'alert alert-' + type + ' alert-dismissible'" v-if="message.length > 0">
    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
    <h5>
      <i v-if="'danger' === type" class="icon fas fa-ban"></i>
      <i v-if="'success' === type" class="icon fas fa-thumbs-up"></i>
      <span v-if="'danger' === type">{{ $t("firefly.flash_error") }}</span>
      <span v-if="'success' === type">{{ $t("firefly.flash_success") }}</span>
    </h5>
    <span v-html="message"></span>
  </div>
</template>
<script>
export default {
  name: "Alert",
  props: ['message', 'type']
}
</script>