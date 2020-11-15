<!--
  - MainBudgetList.vue
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
  <div>
    <div class="row">
      <div class="col">
        <div class="card">
          <div class="card-header">
            <h3 class="card-title">Weekly budgets</h3>
          </div>
          <div class="card-body table-responsive p-0">
            <table class="table table-sm">
              <tbody>
              <tr>
                <td style="width:25%;">Boodschappen (EUR)</td>
                <td style="vertical-align: middle">
                  <div class="progress progress active">
                    <div class="progress-bar bg-success progress-bar-striped" role="progressbar"
                         aria-valuenow="20" aria-valuemin="0" aria-valuemax="100" style="width: 20%;">
                      <span>Spent X of X</span>
                    </div>
                  </div>
                </td>
                <td style="width:10%;">Left or blank</td>
              </tr>
              <tr>
                <td style="width:25%;">Boodschappen (EUR)</td>
                <td style="vertical-align: middle">
                  <div class="progress progress active">
                    <div class="progress-bar bg-success progress-bar-striped" role="progressbar"
                         aria-valuenow="20" aria-valuemin="0" aria-valuemax="100" style="width: 20%;">
                      <span>Spent X of X</span>
                    </div>
                  </div>
                </td>
                <td style="width:10%;">Left or blank</td>
              </tr>
              </tbody>
            </table>
          </div>
          <div class="card-footer">
            <a href="./budgets" class="btn btn-default button-sm"><i class="far fa-money-bill-alt"></i> {{ $t('firefly.go_to_budgets') }}</a>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: "MainBudgetList"
}
</script>

<style scoped>

</style>