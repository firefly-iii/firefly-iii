<!--
  - ExampleComponent.vue
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
    <div>
        <top-boxes/>
        <div class="row">
            <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                <main-account />
            </div>
        </div>
        <div class="row">
            <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                <main-account-list/>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
                <main-budget-chart/>
            </div>
            <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
                <main-category-chart/>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
                <main-debit-chart/>
            </div>
            <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
                <main-credit-chart/>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
                <main-bills-chart/>
            </div>
            <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
                <main-piggy-list/>
            </div>
        </div>
    </div>
</template>

<script>
    export default {
        name: "Dashboard",
        mounted() {
            console.log('Dashboard mounted.')
        }
    }
</script>
