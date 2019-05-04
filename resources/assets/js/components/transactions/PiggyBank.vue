<!--
  - PiggyBank.vue
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
    <div class="form-group" v-if="typeof this.transactionType !== 'undefined' && this.transactionType === 'Transfer'">
        <div class="col-sm-12">
            <select name="piggy_bank[]" class="form-control" v-if="this.piggies.length > 0">
                <option v-for="piggy in this.piggies">{{piggy.name}}</option>
            </select>
        </div>
    </div>
</template>

<script>
    export default {
        name: "PiggyBank",
        props: ['transactionType'],
        mounted() {
            this.loadPiggies();
        },
        data() {
            return {
                piggies: [],
            }
        },
        methods: {
            loadPiggies: function () {
                let URI = document.getElementsByTagName('base')[0].href + "json/piggy-banks";
                axios.get(URI, {}).then((res) => {
                    this.piggies = [
                        {
                            name: '(no piggy bank)',
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