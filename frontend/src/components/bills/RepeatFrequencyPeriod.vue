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
      {{ $t('form.repeat_freq') }}
    </div>
    <div class="input-group" v-if="loading">
      <span class="fas fa-spinner fa-spin"></span>
    </div>
    <div class="input-group" v-if="!loading">
      <select
          ref="repeat_freq"
          v-model="repeat_freq"
          :class="errors.length > 0 ? 'form-control is-invalid' : 'form-control'"
          :title="$t('form.repeat_freq')"
          autocomplete="off"
          :disabled=disabled
          name="repeat_freq"
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
import {configureAxios} from "../../shared/forageStore";
import {mapGetters, mapMutations} from "vuex";

export default {
  name: "RepeatFrequencyPeriod",
  props: {
    value: {},
    errors: {},
    disabled: {
      type: Boolean,
      default: false
    },
  },
  computed: {
    ...mapGetters('root', [ 'cacheKey']),
  },
  data() {
    return {
      periodList: [],
      repeat_freq: this.value,
      loading: true
    }
  },
  methods: {
    ...mapMutations('root', ['refreshCacheKey',]),
    loadPeriods: function () {
      configureAxios().then(async (api) => {
        api.get('./api/v1/configuration/firefly.bill_periods?key=' + this.cacheKey)
            .then(response => {
                    let content = response.data.data.value;
                    for (let i in content) {
                      if (content.hasOwnProperty(i)) {
                        let current = content[i];
                        this.periodList.push({slug: current, title: this.$t('firefly.repeat_freq_' + current)})
                      }
                    }
                    this.loading = false;
                  }
            );
      });
    }
  },
  watch: {
    repeat_freq: function (value) {
      this.$emit('set-field', {field: 'repeat_freq', value: value});
    },
  },
  created() {
    this.loadPeriods()
  }
}
</script>

