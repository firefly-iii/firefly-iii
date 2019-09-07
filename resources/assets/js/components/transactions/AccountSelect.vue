<!--
  - AccountSelect.vue
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
            <div class="input-group">
                <input
                        ref="input"
                        type="text"
                        :placeholder="title"
                        :data-index="index"
                        autocomplete="off"
                        data-role="input"
                        v-on:keypress="handleEnter"
                        :disabled="inputDisabled"
                        class="form-control"
                        v-on:submit.prevent
                        :name="inputName"
                        :title="title">
                <span class="input-group-btn">
            <button
                    v-on:click="clearSource"
                    class="btn btn-default"
                    type="button"><i class="fa fa-trash-o"></i></button>
        </span>
            </div>
            <typeahead
                    :open-on-empty=true
                    :open-on-focus=true
                    v-on:input="selectedItem"
                    :async-src="accountAutoCompleteURI"
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
        props: {
            inputName: String,
            title: String,
            index: Number,
            transactionType: String,
            error: Array,
            accountName: {
                type: String,
                default: ''
            },
            accountTypeFilters: {
                type: Array,
                default: function () {
                    return [];
                }
            },
            defaultAccountTypeFilters: {
                type: Array,
                default: function () {
                    return [];
                }
            }
        },

        data() {
            return {
                accountAutoCompleteURI: null,
                name: null,
                trType: this.transactionType,
                target: null,
                inputDisabled: false,
                allowedTypes: this.accountTypeFilters,
                defaultAllowedTypes: this.defaultAccountTypeFilters
            }
        },
        ready() {
            this.name = this.accountName;
        },
        mounted() {
            this.target = this.$refs.input;
            let types = this.allowedTypes.join(',');
            this.name = this.accountName;
            // console.log('Mounted Types:');
            // console.log(this.allowedTypes);
            this.accountAutoCompleteURI = document.getElementsByTagName('base')[0].href + "json/accounts?types=" + types + "&search=";
            this.triggerTransactionType();
        },

        watch: {
            transactionType() {
                this.triggerTransactionType();
            },
            accountTypeFilters() {
                let types = this.accountTypeFilters.join(',');
                if (0 === this.accountTypeFilters.length) {
                    types = this.defaultAccountTypeFilters.join(',');
                    // console.log('types was empty: ' + types);
                }
                this.accountAutoCompleteURI = document.getElementsByTagName('base')[0].href + "json/accounts?types=" + types + "&search=";
            }
        },
        methods:
            {
                hasError: function () {
                    return this.error.length > 0;
                },
                triggerTransactionType: function () {
                    if (null === this.transactionType) {
                        return;
                    }
                    this.inputDisabled = false;
                    if (this.transactionType.toString() !== '' && this.index > 0) {
                        if (this.transactionType.toString().toLowerCase() === 'transfer') {
                            this.inputDisabled = true;
                            // todo: needs to copy value from very first input

                            return;
                        }

                        if (this.transactionType.toString().toLowerCase() === 'withdrawal' && this.inputName.substr(0, 6).toLowerCase() === 'source') {
                            // todo also clear value?
                            this.inputDisabled = true;
                            return;
                        }

                        if (this.transactionType.toString().toLowerCase() === 'deposit' && this.inputName.substr(0, 11).toLowerCase() === 'destination') {
                            // todo also clear value?
                            this.inputDisabled = true;
                        }
                    }
                },
                selectedItem: function (e) {
                    //console.log('In SelectedItem()');
                    if (typeof this.name === 'undefined') {
                        //console.log('Is undefined');
                        return;
                    }
                    if(typeof this.name === 'string') {
                        //console.log('Is a string.');
                        //this.trType = null;
                        this.$emit('clear:value');
                    }
                    // emit the fact that the user selected a type of account
                    // (influencing the destination)
                    this.$emit('select:account', this.name);
                },
                clearSource: function (e) {
                    //props.value = '';
                    this.name = '';
                    // some event?
                    this.$emit('clear:value')
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