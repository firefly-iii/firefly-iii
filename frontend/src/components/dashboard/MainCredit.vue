<!--
  - MainCrebitChart.vue
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
            <!-- debit = expense -->
            <h3 class="card-title">{{ $t('firefly.income') }}</h3>
        </div>
        <div class="card-body table-responsive p-0">
            <transaction-list-small :transactions="this.transactions" />
        </div>
        <div class="card-footer">
            <a href="./accounts/revenue" class="btn btn-default button-sm"><i class="far fa-money-bill-alt"></i> {{ $t('firefly.go_to_deposits') }}</a>
        </div>
    </div>
</template>

<script>
    export default {
        name: "MainCredit",
        components: {},
        data() {
            return {
                transactions: []
            }
        },
      created() {
            axios.get('./api/v1/transactions?type=deposit&limit=10&start=' + window.sessionStart + '&end=' + window.sessionEnd)
                .then(response => {
                          this.transactions = response.data.data;
                      }
                );
        },
        methods: {
        },
        computed: {},
    }
</script>
