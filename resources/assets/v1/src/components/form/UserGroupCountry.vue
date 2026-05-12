<template>
    <div v-if="showCountrySelector" class="form-group" :class="{ 'has-error': hasError() }">
        <label class="col-sm-4 control-label">
            Country
        </label>

        <div class="col-sm-8">
            <div class="input-group">
                <span v-if="selectedFlagSrc !== ''" class="input-group-addon">
                    <img
                        :src="selectedFlagSrc"
                        :alt="selectedCountry ? selectedCountry.name : 'Country flag'"
                        width="24"
                        height="24"
                        style="display:block;"
                    >
                </span>

                <select
                    v-model.number="country"
                    title="Country"
                    class="form-control"
                    name="user_group_country"
                >
                    <option :value="0">-- Select country --</option>
                    <option
                        v-for="country in countries"
                        :key="country.id"
                        :value="country.id"
                    >
                        {{ country.name }} ({{ country.code }})
                    </option>
                </select>
            </div>

            <p class="help-block">
                Visible only when exchange rate source is country_national.
            </p>

            <ul v-if="hasError()" class="list-unstyled">
                <li v-for="message in error" :key="message" class="text-danger">
                    {{ message }}
                </li>
            </ul>
        </div>
    </div>
</template>

<script>
export default {
    name: "UserGroupCountry",

    props: {
        error: {
            type: Array,
            default() {
                return [];
            },
        },
        value: {
            type: Number,
            default: 0,
        },
        showCountrySelector: {
            type: Boolean,
            default: false,
        },
    },

    data() {
        return {
            country: 0,
            countries: [],
        };
    },

    computed: {
        selectedCountry() {
            const selectedId = parseInt(this.country, 10);

            return this.countries.find((country) => parseInt(country.id, 10) === selectedId) || null;
        },

        selectedFlagSrc() {
            if (null === this.selectedCountry) {
                return '';
            }

            return this.selectedCountry.flag_src ?? '';
        },
    },

    mounted() {
        this.country = this.value || 0;
        this.downloadCountries();
    },

    watch: {
        value(newValue) {
            this.country = parseInt(newValue, 10) || 0;
        },

        country(newValue) {
            this.$emit('input', parseInt(newValue, 10) || 0);
        },
    },

    methods: {
        downloadCountries() {
            axios.get("./api/v1/countries").then((response) => {
                const rows = response.data.data ?? response.data;

                this.countries = rows.map((row) => {
                    const current = row.attributes ?? row;

                    return {
                        id: parseInt(row.id ?? current.id, 10),
                        name: current.name ?? '',
                        code: (current.code ?? '').toUpperCase(),
                        flag_src: current.flag_src ?? current.flag_file ?? '',
                    };
                });
            });
        },

        hasError() {
            return Array.isArray(this.error) && this.error.length > 0;
        },
    },
}
</script>
