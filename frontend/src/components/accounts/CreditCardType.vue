<!--
  - LiabilityType.vue
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
      {{ $t('form.cc_type') }}
    </div>
    <div class="input-group" v-if="loading">
      <span class="fas fa-spinner fa-spin"></span>
    </div>
    <div class="input-group" v-if="!loading">
      <select
          ref="credit_card_type"
          v-model="credit_card_type"
          :class="errors.length > 0 ? 'form-control is-invalid' : 'form-control'"
          :title="$t('form.cc_type')"
          autocomplete="off"
          name="credit_card_type"
          :disabled=disabled
      >
        <option v-for="type in this.typeList" :label="type.title" :value="type.slug">{{ type.title }}</option>
      </select>
    </div>
    <span v-if="errors.length > 0">
      <span v-for="error in errors" class="text-danger small">{{ error }}<br/></span>
    </span>
  </div>
</template>

<script>
export default {
  name: "CreditCardType",
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
      typeList: [],
      credit_card_type: this.value,
      loading: true
    }
  },
  methods: {
    loadRoles: function () {
      //
      axios.get('./api/v1/configuration/firefly.credit_card_types')
          .then(response => {
                  let content = response.data.data.value;
                  for (let i in content) {
                    if (content.hasOwnProperty(i)) {
                      let current = content[i];
                      this.typeList.push({slug: current, title: this.$t('firefly.credit_card_type_' + current)})
                    }
                  }
                  this.loading = false;
                }
          );
    }
  },
  watch: {
    credit_card_type: function (value) {
      this.$emit('set-field', {field: 'credit_card_type', value: value});
    },
    value: function(value) {
      this.credit_card_type = value;
    }

  },
  created() {
    this.loadRoles()
  }
}
</script>

