<!--
  - CustomString.vue
  - Copyright (c) 2019 thegrumpydictator@gmail.com
  -
  - This file is part of Firefly III.
  -
  - Firefly III is free software: you can redistribute it and/or modify
  - it under the terms of the GNU General Public License as published by
  - the Free Software Foundation, either version 3 of the License, or
  - (at your option) any later version.
  -
  - Firefly III is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU General Public License for more details.
  -
  - You should have received a copy of the GNU General Public License
  - along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
  -->

<template>
    <div class="form-group"
         v-bind:class="{ 'has-error': hasError()}"
    >
        <div class="col-sm-12 text-sm">
            {{ title }}
        </div>
        <div class="col-sm-12">
            <input type="text" class="form-control" :name="name"
                   :title="title" autocomplete="off"
                   ref="str"
                   :value="value" @input="handleInput"
                   :placeholder="title">
            <ul class="list-unstyled" v-for="error in this.error">
                <li class="text-danger">{{ error }}</li>
            </ul>
        </div>
    </div>
</template>

<script>
    export default {
        name: "CustomString",
        props: {
            title: String,
            name: String,
            value: String,
            error: Array
        },
        methods: {
            handleInput(e) {
                this.$emit('input', this.$refs.str.value);
            },
            hasError: function () {
                return this.error.length > 0;
            }
        }
    }
</script>

<style scoped>

</style>