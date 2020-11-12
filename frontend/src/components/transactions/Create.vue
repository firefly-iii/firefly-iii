<!--
  - Create.vue
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
  <div class="row">
    <div class="col">
      <div class="card">
        <div class="card-header">
          <h3 class="card-title">Create a new transaction</h3>
        </div>
        <!-- /.card-header -->
        <div class="card-body">
          <div id="accordion">
            <!-- we are adding the .class so bootstrap.js collapse plugin detects it -->
            <div class="card card-primary">
              <div class="card-header">
                <h4 class="card-title">
                  <a data-toggle="collapse" data-parent="#accordion" href="#collapseOne" class="" aria-expanded="true">
                    Basic transaction information
                  </a>
                </h4>
              </div>
              <div id="collapseOne" class="panel-collapse in collapse show" style="">
                <div class="card-body">
                  <div class="row">
                    <div class="col">From</div>
                    <div class="col">To</div>
                  </div>
                  <div class="row">
                    <div class="col">
                      Amount, foreign amount, description, time + date

                    </div>
                  </div>

                </div>
              </div>
            </div>
            <div class="card card-secondary">
              <div class="card-header">
                <h4 class="card-title">
                  <a data-toggle="collapse" data-parent="#accordion" href="#collapseTwo" class="collapsed" aria-expanded="false">
                    Meta information
                  </a>
                </h4>
              </div>
              <div id="collapseTwo" class="panel-collapse collapse">
                <div class="card-body">
                  Budget, category, bill, tags, contract
                </div>
              </div>
            </div>
            <div class="card card-secondary">
              <div class="card-header">
                <h4 class="card-title">
                  <a data-toggle="collapse" data-parent="#accordion" href="#collapseThree" class="collapsed" aria-expanded="false">
                    Extra information
                  </a>
                </h4>
              </div>
              <div id="collapseThree" class="panel-collapse collapse">
                <div class="card-body">
                  Notes, transaction links, custom fields.
                </div>
              </div>
            </div>
          </div>
        </div>
        <!-- /.card-body -->
      </div>
      <!-- button -->
      <div class="row">
        <div class="col">
          <a href="#" class="btn btn-primary">Add a split</a>
        </div>
        <div class="col">
          <p class="float-right">
            <a href="#" class="btn btn-success">Store transaction</a><br/>
          </p>
        </div>
      </div>
      <div class="row">
        <div class="col float-right">
          <p class="text-right">
            <small class="text-muted">Create another another another <input type="checkbox"/></small><br/>
            <small class="text-muted">Return here <input type="checkbox"/></small><br/>
          </p>
        </div>
      </div>


    </div>
  </div>
</template>

<script>
export default {
  name: "Create"
}
</script>

<style scoped>

</style>