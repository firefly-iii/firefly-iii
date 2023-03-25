<!--
  - TransactionDescription.vue
  - Copyright (c) 2023 james@firefly-iii.org
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
  <q-input
    v-model="description"
    hint=" " dense
    :disable="disabledInput"
    :error="hasSubmissionError" :error-message="submissionError"
    :label="$t('firefly.description')" bottom-slots clearable
    outlined
    type="text"/>
</template>



<script>
export default {
  name: "TransactionDescription",
  props: {
    description: {
      type: String,
      required: true
    },
    disabledInput: {
      type: Boolean,
      default: false,
      required: true
    },
    hasSubmissionError: {
      type: Boolean,
      default: false,
      required: true
    },
    submissionError: {
      type: String,
      required: true
    }
  },
  watch: {
    description: {
      handler: function (newVal) {
        this.$emit('update:description', newVal)
      },
      deep: true
    }
  }
}
</script>

<style scoped>

</style>
