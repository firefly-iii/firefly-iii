<!--
  - TransactionDescription.vue
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
    <div class="form-group" v-bind:class="{ 'has-error': hasError()}">
        <div class="col-sm-12">
            <input
                    type="text"
                    class="form-control"
                    name="description[]"
                    title="Description"
                    v-on:keypress="handleEnter"
                    v-on:submit.prevent
                    ref="descr"
                    autocomplete="off"
                    placeholder="Description"
                    @input="handleInput"
            >
            <typeahead
                    :open-on-empty=true
                    :open-on-focus=true
                    v-on:input="selectedItem"
                    :async-src="descriptionAutoCompleteURI"
                    v-model="name"
                    :target="target"
                    item-key="description"
            ></typeahead>
            <ul class="list-unstyled" v-for="error in this.error">
                <li class="text-danger">{{ error }}</li>
            </ul>
        </div>
    </div>
</template>

<script>
    export default {
        props: ['error', 'value', 'index'],
        name: "TransactionDescription",
        mounted() {
            this.target = this.$refs.descr;
            this.descriptionAutoCompleteURI = document.getElementsByTagName('base')[0].href + "json/transaction-journals/all?search=";
        },
        data() {
            return {
                descriptionAutoCompleteURI: null,
                name: null,
                description: null,
                target: null,
            }
        },
        methods: {
            hasError: function () {
                return this.error.length > 0;
            },
            handleInput(e) {
                //this.$emit('input', this.$refs.descr.value);
            },
            handleEnter: function (e) {
                // todo feels sloppy
                if (e.keyCode === 13) {
                    e.preventDefault();
                }
            },
            selectedItem: function (e) {
                console.log('selectedItem for descr()');
                // if (typeof this.name === 'undefined') {
                //     return;
                // }
                // if(typeof this.name === 'string') {
                //     console.log('Is a string.');
                // }
                // // emit the fact that the user selected a type of account
                // // (influencing the destination)
                // this.$emit('select:account', this.name);
            },
        }
    }
</script>

<style scoped>

</style>