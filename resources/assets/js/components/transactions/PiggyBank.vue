<!--
  - PiggyBank.vue
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
         v-if="typeof this.transactionType !== 'undefined' && this.transactionType === 'Transfer'">
        <div class="col-sm-12">
            <select name="piggy_bank[]" ref="piggy" @input="handleInput" class="form-control" v-if="this.piggies.length > 0">
                <option v-for="piggy in this.piggies" :label="piggy.name_with_amount" :value="piggy.id">{{piggy.name_with_amount}}</option>
            </select>
            <ul class="list-unstyled" v-for="error in this.error">
                <li class="text-danger">{{ error }}</li>
            </ul>
        </div>
    </div>
</template>

<script>
    export default {
        name: "PiggyBank",
        props: ['value','transactionType','error', 'no_piggy_bank'],
        mounted() {
            this.loadPiggies();
        },
        data() {
            return {
                piggies: [],
            }
        },
        methods: {
            handleInput(e) {
                this.$emit('input', this.$refs.piggy.value);
            },
            hasError: function () {
                return this.error.length > 0;
            },
            loadPiggies: function () {
                let URI = document.getElementsByTagName('base')[0].href + "json/piggy-banks";
                axios.get(URI, {}).then((res) => {
                    this.piggies = [
                        {
                            name_with_amount: this.no_piggy_bank,
                            id: 0,
                        }
                    ];
                    for (const key in res.data) {
                        if (res.data.hasOwnProperty(key) && /^0$|^[1-9]\d*$/.test(key) && key <= 4294967294) {
                            this.piggies.push(res.data[key]);
                        }
                    }
                });
            }
        }
    }
</script>

<style scoped>

</style>