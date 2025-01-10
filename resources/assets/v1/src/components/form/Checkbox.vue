<!--
  - Active.vue
  - Copyright (c) 2022 james@firefly-iii.org
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
    <label class="col-sm-4 control-label">
      {{ title }}
    </label>

    <div class="col-sm-8">
      <div class="checkbox">
        <label>
          <input v-model=active :name="name" @change="handleInput" type="checkbox" value="1">
        </label>
      </div>
      <p class="help-block" v-text="$t('firefly.webhook_active_form_help')"></p>
    </div>
  </div>
</template>

<script>
export default {
  name: "Checkbox",
  props: {
    name: {
      type: String,
    },
    title: {
      type: String,
    },
    value: {
      type: Boolean,
    }
  },
  data() {
    return {
      active: true,
    };
  },
  mounted() {
    this.active = this.value;
  },
  methods: {
    handleInput() {
      // console.log(this.active);
      this.$emit('input', this.active);
    },
  },
  watch: {
    value: function (val) {
      this.active = val;

    }
  }
}
</script>
