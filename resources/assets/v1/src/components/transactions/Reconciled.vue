<!--
  - Reconciled.vue
  - Copyright (c) 2025 james@firefly-iii.org.
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
  - along with this program.  If not, see https://www.gnu.org/licenses/.
  -->


<template>
    <div class="form-group" v-bind:class="{ 'has-error': hasError()}">
        <div class="col-sm-8 col-sm-offset-4 text-sm">

        </div>
        <label ref="cur" class="col-sm-4 control-label">
            {{ $t('firefly.is_reconciled') }}
        </label>
        <div class="col-sm-8">
            <div class="checkbox">
                <label>
                    <input name="create_another" @input="handleInput" type="checkbox" v-model="reconciled">
                </label>
            </div>
        </div>
        <ul v-for="error in this.error" class="list-unstyled">
            <li class="text-danger">{{ error }}</li>
        </ul>
    </div>
</template>

<script>

export default {
    name: "Reconciled",
    props: {
        value: Boolean,
        error: Array
    },
    data() {
        return {
            reconciled: this.value
        }
    },
    watch: {
        reconciled: function () {
            // console.log('reconciled is ' + this.reconciled);
            this.$emit('input', this.reconciled);
        },
    },
    methods: {
        handleInput(e) {

        },
        hasError: function () {
            return this.error.length > 0;
        },
    }
}
</script>

<style scoped>

</style>
