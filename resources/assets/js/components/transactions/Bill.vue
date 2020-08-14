
<!--
  - Bill.vue
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
    <div class="form-group"
         v-bind:class="{ 'has-error': hasError()}"
         v-if="typeof this.transactionType === 'undefined' || this.transactionType === 'withdrawal' || this.transactionType === 'Withdrawal' || this.transactionType === '' || null === this.transactionType">
        <div class="col-sm-12 text-sm">
            {{ $t('firefly.bill') }}
        </div>
        <div class="col-sm-12">
            <select
            name="bill[]"
            ref="bill"
            v-model="selected"
            @input="handleInput"
            v-on:change="signalChange"
            :title="$t('firefly.bill')"
            class="form-control"
             v-if="this.bills.length > 0">
                <option v-for="cBill in this.bills"
                    :label="cBill.name"
                    :value="cBill.id">{{ cBill.name }}
                </option>
            </select>
            <p class="help-block" v-if="this.bills.length === 1" v-html="$t('firefly.no_bill_pointer')"></p>
            <ul class="list-unstyled" v-for="error in this.error">
                <li class="text-danger">{{ error }}</li>
            </ul>
        </div>
    </div>
</template>

<script>
    export default {
        name: "Bill",
        props: {
            transactionType: String,
            value: {
                type: [String, Number],
                default: 0
            },
            error: Array,
            no_bill: String,
        },
        mounted() {
            this.loadBills();
        },
        data() {
            return {
                selected: this.value ?? 0,
                bills: [],
            }
        },
        methods: {
            // Fixes edit change bill not updating on every broswer
            signalChange: function(e) {
                this.$emit('input', this.$refs.bill.value);
            },
            handleInput(e) {
                this.$emit('input', this.$refs.bill.value);
            },
            hasError: function () {
                return this.error.length > 0;
            },
            loadBills: function () {
                let URI = document.getElementsByTagName('base')[0].href + 'api/v1/autocomplete/bills?limit=1337';
                axios.get(URI, {}).then((res) => {
                        this.bills = [
                            {
                                name: this.no_bill,
                                id: 0,
                            }
                        ];
                    for (const key in res.data) {
                        if (res.data.hasOwnProperty(key) && /^0$|^[1-9]\d*$/.test(key) && key <= 4294967294) {
                            this.bills.push(res.data[key]);
                        }
                    }
                });
            }
        }
    }
</script>

<style scoped>

</style>
