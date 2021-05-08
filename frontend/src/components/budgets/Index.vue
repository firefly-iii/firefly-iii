<!--
  - Index.vue
  - Copyright (c) 2021 james@firefly-iii.org
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
    <p>
      <span class="d-block">(all)</span>
      <span class="d-none d-xl-block">xl</span>
      <span class="d-none d-lg-block d-xl-none">lg</span>
      <span class="d-none d-md-block d-lg-none">md</span>
      <span class="d-none d-sm-block d-md-none">sm</span>
      <span class="d-block d-sm-none">xs</span>
    </p>

    <div class="row">
      <div class="col-xl-2 col-lg-4 col-md-4 col-sm-4 col-6">
        <div class="card card-primary">
          <div class="card-header">
            <h3 class="card-title">Budgets</h3>
          </div>
          <div class="card-body">
            Budget X<br>
            Budget Y<br>
            Budget X<br>
            Budget Y<br>
            Budget X<br>
            Budget Y<br>
            Budget X<br>
            Budget Y<br>
          </div>
        </div>
      </div>
      <div class="col-xl-10 col-lg-8 col-md-8 col-sm-8 col-6">
          <div class="container-fluid" style="overflow:scroll;">
            <div class="d-flex flex-row flex-nowrap">
              <div class="card card-body-budget" v-for="n in 5">
                <div class="card-header">
                  <h3 class="card-title">Maand yXz</h3>
                </div>
                <div class="card-body">
                  Some text<br>
                  Some text<br>
                  Some text<br>

                </div>
              </div>
            </div>
          </div>
      </div>
    </div>
  </div>

</template>

<script>
export default {
  name: "Index",
  created() {

  }
}
</script>

<style scoped>

.card-body-budget {
  min-width: 300px;
  margin-right: 5px;
}

.holder-titles {
  display: flex;
  flex-direction: column-reverse;
}

.title-block {
  border: 1px red solid;
}

.holder-blocks {
  display: flex;
  flex-direction: column-reverse;
}

.budget-block {
  border: 1px blue solid;
}

.budget-block-unused {
  border: 1px green solid;
}

.budget-block-unset {
  border: 1px purple solid;
}

</style>