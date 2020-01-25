<!--
  - Category.vue
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
    <div class="form-group" v-bind:class="{ 'has-error': hasError()}">
        <div class="col-sm-12 text-sm">
            {{ $t('firefly.category') }}
        </div>
        <div class="col-sm-12">
            <div class="input-group">
                <input
                        ref="input"
                        :value="value"
                        @input="handleInput"
                        type="text"
                        v-bind:placeholder="$t('firefly.category')"
                        autocomplete="off"
                        data-role="input"
                        v-on:keypress="handleEnter"
                        class="form-control"
                        v-on:submit.prevent
                        name="category[]"
                        v-bind:title="$t('firefly.category')">
                <span class="input-group-btn">
            <button
                    v-on:click="clearCategory"
                    class="btn btn-default"
                    type="button"><i class="fa fa-trash-o"></i></button>
        </span>
            </div>
            <typeahead
                    :open-on-empty=true
                    :open-on-focus=true
                    v-on:input="selectedItem"
                    :async-src="categoryAutoCompleteURI"
                    v-model="name"
                    :target="target"
                    item-key="name"
            ></typeahead>
            <ul class="list-unstyled" v-for="error in this.error">
                <li class="text-danger">{{ error }}</li>
            </ul>
        </div>
    </div>
</template>

<script>
    export default {
        name: "Category",
        props: {
            value: String,
            inputName: String,
            error: Array,
            accountName: {
                type: String,
                default: ''
            },
        },
        data() {
            return {
                categoryAutoCompleteURI: null,
                name: null,
                target: null,
            }
        },
        ready() {
            this.name = this.accountName;
        },
        mounted() {
            this.target = this.$refs.input;
            this.categoryAutoCompleteURI = document.getElementsByTagName('base')[0].href + "json/categories?search=";
        },
        methods: {
            hasError: function () {
                return this.error.length > 0;
            },
            handleInput(e) {
                if (typeof this.$refs.input.value === 'string') {
                    this.$emit('input', this.$refs.input.value);
                    return;
                }
                this.$emit('input', this.$refs.input.value.name);

            },
            clearCategory: function () {
                //props.value = '';
                this.name = '';
                this.$refs.input.value = '';
                this.$emit('input', this.$refs.input.value);
                // some event?
                this.$emit('clear:category')
            },
            selectedItem: function (e) {
                if (typeof this.name === 'undefined') {
                    return;
                }
                // emit the fact that the user selected a type of account
                // (influencing the destination)
                this.$emit('select:category', this.name);

                if (typeof this.name === 'string') {
                    this.$emit('input', this.name);
                    return;
                }
                this.$emit('input', this.name.name);
            },
            handleEnter: function (e) {
                // todo feels sloppy
                if (e.keyCode === 13) {
                    e.preventDefault();
                }
            }
        }
    }
</script>

<style scoped>

</style>