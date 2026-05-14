<!--
  - UserGroupCountry.vue
  -
  - Country selector for an administration (user group).
  - Lists only countries that have a registered national-bank exchange-rate
  - provider (server-side filter via `?with_provider=1`).
  - An empty option lets the admin clear the binding.
  -->

<template>
  <div class="form-group" v-bind:class="{ 'has-error': hasError() }">
    <label class="col-sm-4 control-label">
      {{ $t('form.administration_country') }}
    </label>
    <div class="col-sm-8">
      <select
          v-model="country"
          :title="$t('form.administration_country')"
          class="form-control"
          name="user_group_country"
      >
        <option :value="0">{{ $t('firefly.administration_country_none') }}</option>
        <option v-for="c in countries"
                :key="c.id"
                :label="c.name"
                :value="c.id">
          {{ c.name }} ({{ c.code }})
        </option>
      </select>
      <p class="help-block" v-text="$t('firefly.administration_country_form_help')"></p>
      <ul v-for="(err, idx) in this.error" :key="idx" class="list-unstyled">
        <li class="text-danger">{{ err }}</li>
      </ul>
    </div>
  </div>
</template>

<script>
export default {
  name: "UserGroupCountry",
  data() {
    return {
      country: 0,
      countries: [],
    };
  },
  props: {
    error: {
      type: Array,
      required: true,
      default() { return [] }
    },
    value: {
      // accept 0 / null / undefined
      type: [Number, String],
      required: false,
      default: 0,
    }
  },
  mounted() {
    this.country = parseInt(this.value) || 0;
    this.downloadCountries();
  },
  watch: {
    value(newValue) {
      this.country = parseInt(newValue) || 0;
    },
    country(newValue) {
      // Emit either a numeric id, or 0 → null (cleared) to the parent.
      this.$emit('input', newValue ? parseInt(newValue) : 0);
    }
  },
  methods: {
    downloadCountries() {
      axios.get('./api/v1/countries?with_provider=1').then((response) => {
        // response is a flat array (Eloquent collection ->toArray()).
        const items = Array.isArray(response.data) ? response.data : (response.data.data || []);
        this.countries = items.map((row) => ({
          id: parseInt(row.id),
          code: row.code,
          name: row.name,
        }));
      });
    },
    hasError() {
      return this.error?.length > 0;
    }
  },
}
</script>
