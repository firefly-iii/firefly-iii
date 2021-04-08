<!--
  - AssetAccountRole.vue
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
      {{ $t('form.account_role') }}
    </div>
    <div class="input-group" v-if="loading">
      <i class="fas fa-spinner fa-spin"></i>
    </div>
    <div class="input-group" v-if="!loading">
      <select
          ref="account_role"
          v-model="account_role"
          :class="errors.length > 0 ? 'form-control is-invalid' : 'form-control'"
          :title="$t('form.account_role')"
          autocomplete="off"
          name="account_role"
          :disabled=disabled
      >
        <option v-for="role in this.roleList" :label="role.title" :value="role.slug">{{ role.title }}</option>
      </select>
    </div>
    <span v-if="errors.length > 0">
      <span v-for="error in errors" class="text-danger small">{{ error }}<br/></span>
    </span>
  </div>
</template>

<script>
export default {
  name: "AssetAccountRole",
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
      roleList: [],
      account_role: this.value,
      loading: false
    }
  },
  methods: {
    loadRoles: function () {
      //
      axios.get('./api/v1/configuration/firefly.accountRoles')
          .then(response => {
                  let content = response.data.data.value;
                  for (let i in content) {
                    if (content.hasOwnProperty(i)) {
                      let current = content[i];
                      this.roleList.push({slug: current, title: this.$t('firefly.account_role_' + current)})
                    }
                  }
                }
          );
    }
  },
  watch: {
    account_role: function (value) {
      this.$emit('set-field', {field: 'account_role', value: value});
    },
  },
  created() {
    this.loadRoles()
  }
}
</script>

<style scoped>

</style>