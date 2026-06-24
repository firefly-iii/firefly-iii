<!--
  - TransactionDescription.vue
  - Copyright (c) 2019 james@firefly-iii.org
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

</template>

<script>
export default {
  props: {
    error: {
      type: Array,
      required: true,
      default() {
        return []
      }
    },
    value: {
      type: String,
      required: true,
    }
  },
  name: "Title",
  mounted() {
    this.title = this.value;
  },
  watch: {
    value() {
      this.title = this.value;
    }
  },
  components: {},
  data() {
    return {
      title: ''
    }
  },
  methods: {
    hasError: function () {
      return this.error.length > 0;
    },
    clearTitle: function () {
      this.title = '';
    },
    handleInput() {
      this.$emit('input', this.title);
    },
  }
}
</script>
