<!--
  - Amount.vue
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
        <div class="col-sm-8 col-sm-offset-4 text-sm">
            {{ $t('firefly.amount') }}
        </div>
        <label class="col-sm-4 control-label" ref="cur"></label>
        <div class="col-sm-8">
            <input type="number" 
            @input="handleInput" 
            ref="amount" 
            :value="value" 
            step="any" 
            class="form-control"
            name="amount[]"
            :title="$t('firefly.amount')" 
            autocomplete="off" 
            v-bind:placeholder="$t('firefly.amount')">
            <ul class="list-unstyled" v-for="error in this.error">
                <li class="text-danger">{{ error }}</li>
            </ul>
        </div>
    </div>
</template>

<script>
    export default {
        name: "Amount",
        props: ['source', 'destination', 'transactionType', 'value', 'error'],
        data() {
            return {
                sourceAccount: this.source,
                destinationAccount: this.destination,
                type: this.transactionType
            }
        },
        methods: {
            handleInput(e) {
                this.$emit('input', this.$refs.amount.value);
            },
            hasError: function () {
                return this.error.length > 0;
            },
            changeData: function () {
                let transactionType = this.transactionType;
                // reset of all are empty:
                //console.log('Type   "' + transactionType + '"');
                //console.log('Source "' + this.source.name + '"');
                //console.log('Dest   "' + this.destination.name + '"');
                if (!transactionType && !this.source.name && !this.destination.name) {
                    $(this.$refs.cur).text('');

                    return;
                }
                if(null === transactionType) {
                    transactionType = '';
                }
                if ('' === transactionType && '' !== this.source.currency_name) {
                    $(this.$refs.cur).text(this.source.currency_name);
                    return;
                }
                if ('' === transactionType && '' !== this.destination.currency_name) {
                    $(this.$refs.cur).text(this.destination.currency_name);
                    return;
                }
                if (transactionType.toLowerCase() === 'withdrawal' ||
                    transactionType.toLowerCase() === 'reconciliation' ||
                    transactionType.toLowerCase() === 'transfer') {
                    $(this.$refs.cur).text(this.source.currency_name);
                    return;
                }
                if (transactionType === 'Deposit') {
                    $(this.$refs.cur).text(this.destination.currency_name);
                }
            }
        },
        watch: {
            source: function () {
                this.changeData();
            },
            destination: function () {
                this.changeData();
            },
            transactionType: function () {
                this.changeData();
            }
        },
        mounted() {
            this.changeData();
        }
    }
</script>

<style scoped>

</style>