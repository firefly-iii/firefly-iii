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
        <div class="col-sm-12 text-sm">
            {{ $t('firefly.piggy_bank') }}

        </div>
        <div class="col-sm-12">
            <select name="piggy_bank[]" ref="piggy" @input="handleInput" class="form-control">
                <optgroup v-for="(option, key) in this.piggies" v-bind:label="key">
                    <option v-for="piggy in option.piggies" :label="piggy.name_with_balance" :value="piggy.id">{{piggy.name_with_balance}}</option>
                </optgroup>
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
                let URI = document.getElementsByTagName('base')[0].href + "api/v1/autocomplete/piggy-banks-with-balance";
                axios.get(URI, {}).then((res) => {
                    let tempList = {
                        0: {
                            group: {
                                title: this.$t('firefly.default_group_title_name')
                            },
                            piggies: [
                                {
                                    name_with_balance: this.no_piggy_bank,
                                    id: 0,
                                }
                            ],
                        }
                    };
                    for (const key in res.data) {
                        if (res.data.hasOwnProperty(key) && /^0$|^[1-9]\d*$/.test(key) && key <= 4294967294) {
                            // add to temp list
                            let currentPiggy = res.data[key];
                            if (currentPiggy.objectGroup) {
                                let groupOrder = currentPiggy.objectGroup.order;
                                if (!tempList[groupOrder]) {
                                    tempList[groupOrder] = {
                                        group: {
                                            title: currentPiggy.objectGroup.title
                                        },
                                        piggies: [],
                                    };
                                }
                                tempList[groupOrder].piggies.push({name_with_balance: currentPiggy.name_with_balance, id: currentPiggy.id});
                            }
                            if (!currentPiggy.objectGroup) {
                                // add to empty one:
                                tempList[0].piggies.push({name_with_balance: currentPiggy.name_with_balance, id: currentPiggy.id});
                            }
                            //console.log(currentPiggy);
                            this.piggies.push(res.data[key]);
                        }
                    }
                    const ordered = {};
                    Object.keys(tempList).sort().forEach(function(key) {
                        let groupName = tempList[key].group.title;
                        ordered[groupName] = tempList[key];
                    });
                    // final list:

                    this.piggies = ordered;
                    console.log(ordered);
                });
            }
        }
    }
</script>

<style scoped>

</style>
