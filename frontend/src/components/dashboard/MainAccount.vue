<!--
  - MainAccount.vue
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
      <h3 class="card-title">{{ $t('firefly.yourAccounts') }}</h3>
    </div>
    <div class="card-body">
      <div>
        <canvas id="mainAccountsChart" style="min-height: 400px; height: 400px; max-height: 400px; max-width: 100%;"></canvas>
      </div>
    </div>
    <div class="card-footer">
      <a href="./accounts/asset" class="btn btn-default button-sm"><i class="far fa-money-bill-alt"></i> {{ $t('firefly.go_to_asset_accounts') }}</a>
    </div>
  </div>
</template>

<script>
import DataConverter from "../charts/DataConverter";
import DefaultLineOptions from "../charts/DefaultLineOptions";

export default {
  name: "MainAccount",
  created() {
    axios.get('./api/v1/chart/account/overview?start=' + window.sessionStart + '&end=' + window.sessionEnd)
        .then(response => {

          let chartData = DataConverter.methods.convertChart(response.data);
          chartData = DataConverter.methods.colorizeLineData(chartData);
          let lineChartCanvas = $('#mainAccountsChart').get(0).getContext('2d');
          new Chart(lineChartCanvas, {
            type: 'line',
            data: chartData,
            options: DefaultLineOptions.methods.getDefaultOptions()
          });
        });
  },
}
</script>
