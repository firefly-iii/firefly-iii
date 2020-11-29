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
      <div class="col">
        <main-account/>
      </div>
    </div>
    <main-account-list/>

    <div class="row">
      <div class="col">
        <main-budget-list/>
      </div>
    </div>

    <div class="row">
      <div class="col">
        <main-category-list />
      </div>
    </div>
    <div class="row">
      <div class="col-lg-6 col-md-12 col-sm-12 col-xs-12">
        <main-debit-list />
      </div>
      <div class="col-lg-6 col-md-12 col-sm-12 col-xs-12">
        <main-credit-list />
      </div>
    </div>

    <!--


    <div class="row">
        <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
            <main-piggy-list/>
        </div>
        <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
            <main-bills-list/>
        </div>
    </div>
    -->
  </div>
</template>

<script>
export default {
  name: "Dashboard",
  created() {
    if (!localStorage.currencyPreference) {
      this.getCurrencyPreference();
    }
  },
  methods: {
    getCurrencyPreference: function () {
      axios.get('./api/v1/currencies/default')
          .then(response => {
            localStorage.currencyPreference = JSON.stringify({
              id: parseInt(response.data.data.id),
              name: response.data.data.attributes.name,
              symbol: response.data.data.attributes.symbol,
              code: response.data.data.attributes.code,
              decimal_places: parseInt(response.data.data.attributes.decimal_places),
            });
          }).catch(err => {
        console.log('Got error response.');
        console.error(err);
        localStorage.currencyPreference = JSON.stringify({
          id: 1,
          name: 'Euro',
          symbol: '€',
          code: 'EUR',
          decimal_places: 2
        });
      });
    }
  }
}
</script>
