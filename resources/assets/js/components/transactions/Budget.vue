<!--
  - Budget.vue
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
    <div class="form-group" v-if="typeof this.transactionType !== 'undefined' && this.transactionType === 'Withdrawal'">
        <div class="col-sm-12">
            <select name="budget[]" ref="budget" @input="handleInput" class="form-control" v-if="this.budgets.length > 0">
                <option v-for="budget in this.budgets" :label="budget.name" :value="budget.id">{{budget.name}}</option>
            </select>
        </div>
    </div>
</template>

<script>
    export default {
        name: "Budget",
        props: ['transactionType','value'],
        mounted() {
            this.loadBudgets();
        },
        data() {
            return {
                budgets: [],
            }
        },
        methods: {
            handleInput(e) {
                this.$emit('input', this.$refs.budget.value);
            },
            loadBudgets: function () {
                let URI = document.getElementsByTagName('base')[0].href + "json/budgets";
                axios.get(URI, {}).then((res) => {
                    this.budgets = [
                        {
                            name: '(no budget)',
                            id: 0,
                        }
                    ];
                    for (const key in res.data) {
                        if (res.data.hasOwnProperty(key) && /^0$|^[1-9]\d*$/.test(key) && key <= 4294967294) {
                            this.budgets.push(res.data[key]);
                        }
                    }
                });
            }
        }
    }
</script>

<style scoped>

</style>