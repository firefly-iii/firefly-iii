<!--
  - Show.vue
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
      <div class="col-lg-12 col-md-6 col-sm-12 col-xs-12">
        <!-- Custom Tabs -->
        <div class="card">
          <div class="card-header d-flex p-0">
            <h3 class="card-title p-3">Tabs</h3>
            <ul class="nav nav-pills ml-auto p-2">
              <li class="nav-item"><a class="nav-link active" href="#main_chart" data-toggle="tab">Chart</a></li>
              <li class="nav-item"><a class="nav-link" href="#budgets" data-toggle="tab">Budgets</a></li>
              <li class="nav-item"><a class="nav-link" href="#categories" data-toggle="tab">Categories</a></li>
            </ul>
          </div><!-- /.card-header -->
          <div class="card-body">
            <div class="tab-content">
              <div class="tab-pane active" id="main_chart">
                1: main chart
              </div>
              <!-- /.tab-pane -->
              <div class="tab-pane" id="budgets">
                2: tree map from/to budget
              </div>
              <!-- /.tab-pane -->
              <div class="tab-pane" id="categories">
                2: tree map from/to cat
              </div>
              <!-- /.tab-pane -->
            </div>
            <!-- /.tab-content -->
          </div><!-- /.card-body -->
        </div>
        <!-- ./card -->
      </div>
    </div>
    <div class="row">
      <div class="col-lg-12 col-md-6 col-sm-12 col-xs-12">
        <div class="card">
          <div class="card-header">
            <h3 class="card-title">
              List
            </h3>
          </div>
          <div class="card-body">
            Transaction list
          </div>
        </div>
      </div>
    </div>
    <div class="row">
      <div class="col-lg-12 col-md-6 col-sm-12 col-xs-12">
        <div class="card">
          <div class="card-header">
            <h3 class="card-title">
              Blocks
            </h3>
          </div>
          <div class="card-body">
            Blocks
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: "Show",

}
</script>

