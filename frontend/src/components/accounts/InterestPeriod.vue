<!--
  - InterestPeriod.vue
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
      {{ $t('form.interest_period') }}
    </div>
    <div class="input-group" v-if="loading">
      <i class="fas fa-spinner fa-spin"></i>
    </div>
    <div class="input-group" v-if="!loading">
      <select
          ref="interest_period"
          v-model="interest_period"
          :class="errors.length > 0 ? 'form-control is-invalid' : 'form-control'"
          :title="$t('form.interest_period')"
          autocomplete="off"
          :disabled=disabled
          name="interest_period"
      >
        <option v-for="period in this.periodList" :label="period.title" :value="period.slug">{{ period.title }}</option>
      </select>
    </div>
    <span v-if="errors.length > 0">
      <span v-for="error in errors" class="text-danger small">{{ error }}<br/></span>
    </span>
  </div>
</template>

<script>
export default {
  name: "InterestPeriod",
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
      periodList: [],
      interest_period: this.value,
      loading: true
    }
  },
  methods: {
    loadPeriods: function () {
      //
      axios.get('./api/v1/configuration/firefly.interest_periods')
          .then(response => {
                  let content = response.data.data.value;
                  for (let i in content) {
                    if (content.hasOwnProperty(i)) {
                      let current = content[i];
                      this.periodList.push({slug: current, title: this.$t('firefly.interest_calc_' + current)})
                    }
                  }
                  this.loading = false;
                }
          );
    }
  },
  watch: {
    interest_period: function (value) {
      this.$emit('set-field', {field: 'interest_period', value: value});
    },
  },
  created() {
    this.loadPeriods()
  }
}
</script>

<style scoped>

</style>