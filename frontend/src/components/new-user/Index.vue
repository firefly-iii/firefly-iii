<!--
  - Index.vue
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
      <div id="accordion">
        <!-- we are adding the .class so bootstrap.js collapse plugin detects it -->
        <div class="card card-primary">
          <div class="card-header">
            <h4 class="card-title">
              <a data-parent="#accordion" data-toggle="collapse" href="#collapseOne">
                Create new accounts
              </a>
            </h4>
          </div>
          <div id="collapseOne" class="panel-collapse collapse show">
            <div class="card-body">
              <div class="row">
                <div class="col">
                  <p>Explain</p>
                </div>
              </div>
              <div class="row">
                <div class="col-lg-4">
                  A
                </div>
                <div class="col-lg-8">
                  B
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="card card-secondary">
          <div class="card-header">
            <h4 class="card-title">
              <a data-parent="#accordion" data-toggle="collapse" href="#collapseTwo">
                Collapsible Group Danger
              </a>
            </h4>
          </div>
          <div id="collapseTwo" class="panel-collapse collapse">
            <div class="card-body">
              Anim pariatur cliche reprehenderit, enim eiusmod high life accusamus terry richardson ad squid.
              3
              wolf moon officia aute, non cupidatat skateboard dolor brunch. Food truck quinoa nesciunt
              laborum
              eiusmod. Brunch 3 wolf moon tempor, sunt aliqua put a bird on it squid single-origin coffee
              nulla
              assumenda shoreditch et. Nihil anim keffiyeh helvetica, craft beer labore wes anderson cred
              nesciunt sapiente ea proident. Ad vegan excepteur butcher vice lomo. Leggings occaecat craft
              beer
              farm-to-table, raw denim aesthetic synth nesciunt you probably haven't heard of them accusamus
              labore sustainable VHS.
            </div>
          </div>
        </div>
        <div class="card card-secondary">
          <div class="card-header">
            <h4 class="card-title">
              <a data-parent="#accordion" data-toggle="collapse" href="#collapseThree">
                Collapsible Group Success
              </a>
            </h4>
          </div>
          <div id="collapseThree" class="panel-collapse collapse">
            <div class="card-body">
              Anim pariatur cliche reprehenderit, enim eiusmod high life accusamus terry richardson ad squid.
              3
              wolf moon officia aute, non cupidatat skateboard dolor brunch. Food truck quinoa nesciunt
              laborum
              eiusmod. Brunch 3 wolf moon tempor, sunt aliqua put a bird on it squid single-origin coffee
              nulla
              assumenda shoreditch et. Nihil anim keffiyeh helvetica, craft beer labore wes anderson cred
              nesciunt sapiente ea proident. Ad vegan excepteur butcher vice lomo. Leggings occaecat craft
              beer
              farm-to-table, raw denim aesthetic synth nesciunt you probably haven't heard of them accusamus
              labore sustainable VHS.
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: "Index"
}
</script>

<style scoped>

</style>