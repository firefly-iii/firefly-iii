<!--
  - MainPiggyList.vue
  - Copyright (c) 2020 james@firefly-iii.org
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
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">{{ $t('firefly.piggy_banks') }}</h3>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-striped">
              <caption style="display:none;">{{ $t('firefly.piggy_banks') }}</caption>
                <thead>
                <tr>
                    <th scope="col" style="width:35%;">{{ $t('list.piggy_bank') }}</th>
                    <th scope="col" style="width:40%;">{{ $t('list.percentage') }}</th>
                    <th scope="col" style="width:25%;text-align: right;">{{ $t('list.amount') }}</th>
                </tr>
                </thead>
                <tbody>
                <tr v-for="piggy in this.piggy_banks">
                    <td>{{ piggy.attributes.name }}
                    <br /><small class="text-muted">{{ piggy.attributes.object_group_title }}</small>
                    </td>
                    <td>
                        <div class="progress-group">
                            <div class="progress progress-sm">
                                <div class="progress-bar primary" v-if="piggy.attributes.pct < 100" :style="{'width': piggy.attributes.pct + '%'}"></div>
                                <div class="progress-bar bg-success" v-if="100 === piggy.attributes.pct" :style="{'width': piggy.attributes.pct + '%'}"></div>
                            </div>
                        </div>
                    </td>
                    <td style="text-align: right;">
                        <span class="text-success">
                            {{ Intl.NumberFormat('en-US', {style: 'currency', currency: piggy.attributes.currency_code}).format(piggy.attributes.current_amount) }}
                        </span>
                        of
                        <span class="text-success">{{ Intl.NumberFormat('en-US', {style: 'currency', currency: piggy.attributes.currency_code}).format(piggy.attributes.target_amount) }}</span>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            <a href="./piggy-banks" class="btn btn-default button-sm"><i class="far fa-money-bill-alt"></i> {{ $t('firefly.go_to_piggies') }}</a>
        </div>
    </div>
</template>

<script>
    export default {
        name: "MainPiggyList",
        mounted() {
            axios.get('./api/v1/piggy_banks')
                .then(response => {
                          this.loadPiggyBanks(response.data.data);
                      }
                );
        },
        methods: {
            loadPiggyBanks(data) {
                for (let key in data) {
                    if (data.hasOwnProperty(key) && /^0$|^[1-9]\d*$/.test(key) && key <= 4294967294) {
                        let piggy = data[key];
                        if(0.0 !== parseFloat(piggy.attributes.left_to_save)) {
                            piggy.attributes.pct = (parseFloat(piggy.attributes.current_amount) / parseFloat(piggy.attributes.target_amount)) * 100;
                            this.piggy_banks.push(piggy);
                        }
                    }
                }
                this.piggy_banks.sort(function(a, b) {
                    return b.attributes.pct - a.attributes.pct;
                });
            }
        },
        data() {
            return {
                piggy_banks: []
            }
        }
    }
</script>

<style scoped>

</style>
