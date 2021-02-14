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

    <!-- body if loading -->
    <div class="card-body" v-if="loading && !error">
      <div class="text-center">
        <i class="fas fa-spinner fa-spin"></i>
      </div>
    </div>
    <!-- body if error -->
    <div class="card-body" v-if="error">
      <div class="text-center">
        <i class="fas fa-exclamation-triangle text-danger"></i>
      </div>
    </div>
    <!-- body if normal -->
    <div class="card-body table-responsive p-0" v-if="!loading && !error">
      <table class="table table-striped">
        <caption style="display:none;">{{ $t('firefly.piggy_banks') }}</caption>
        <thead>
        <tr>
          <th scope="col" style="width:35%;">{{ $t('list.piggy_bank') }}</th>
          <th scope="col" style="width:40%;">{{ $t('list.percentage') }} <small>/ {{ $t('list.amount') }}</small></th>
        </tr>
        </thead>
        <tbody>
        <tr v-for="piggy in this.piggy_banks">
          <td>
            <a :href="'./piggy-banks/show/' + piggy.id" :title="piggy.attributes.name">{{ piggy.attributes.name }}</a>
            <small v-if="piggy.attributes.object_group_title" class="text-muted">
              <br/>
              {{ piggy.attributes.object_group_title }}
            </small>
          </td>
          <td>
            <div class="progress-group">
              <div class="progress progress-sm">
                <div class="progress-bar progress-bar-striped primary" v-if="piggy.attributes.pct < 100" :style="{'width': piggy.attributes.pct + '%'}"></div>
                <div class="progress-bar progress-bar-striped bg-success" v-if="100 === piggy.attributes.pct"
                     :style="{'width': piggy.attributes.pct + '%'}"></div>
              </div>
            </div>
            <span class="text-success">
                            {{
                Intl.NumberFormat(locale, {style: 'currency', currency: piggy.attributes.currency_code}).format(piggy.attributes.current_amount)
              }}
                        </span>
            of
            <span class="text-success">{{
                Intl.NumberFormat(locale, {
                  style: 'currency',
                  currency: piggy.attributes.currency_code
                }).format(piggy.attributes.target_amount)
              }}</span>
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
  data() {
    return {
      piggy_banks: [],
      loading: true,
      error: false,
      locale: 'en-US'
    }
  },
  created() {
    this.locale = localStorage.locale ?? 'en-US';
    axios.get('./api/v1/piggy_banks')
        .then(response => {
                this.loadPiggyBanks(response.data.data);
                this.loading = false;
              }
        ).catch(error => {
      this.error = true
    });
  },
  methods: {
    loadPiggyBanks(data) {
      for (let key in data) {
        if (data.hasOwnProperty(key) && /^0$|^[1-9]\d*$/.test(key) && key <= 4294967294) {
          let piggy = data[key];
          if (0.0 !== parseFloat(piggy.attributes.left_to_save)) {
            piggy.attributes.pct = (parseFloat(piggy.attributes.current_amount) / parseFloat(piggy.attributes.target_amount)) * 100;
            this.piggy_banks.push(piggy);
          }
        }
      }
      this.piggy_banks.sort(function (a, b) {
        return b.attributes.pct - a.attributes.pct;
      });
    }
  }
}
</script>

<style scoped>

</style>
