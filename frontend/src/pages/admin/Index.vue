<!--
  - Index.vue
  - Copyright (c) 2022 james@firefly-iii.org
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
  <q-page>
    <div class="row">
      <div class="col-6">
        <q-card bordered class="q-mx-sm">
          <q-card-section>
            <div class="text-h6">Firefly III administration</div>
          </q-card-section>
          <q-card-section>
            <div class="row">
              <div class="col-12">
                <!-- TODO cloned from Preferences -->
                configuration.permission_update_check
              </div>
            </div>
          </q-card-section>
        </q-card>
      </div>
      <div class="col-6">
        <q-card bordered class="q-mx-sm">
          <q-card-section>
            <div class="text-h6">Firefly III information</div>
          </q-card-section>
          <q-card-section>
            <div class="row">
              <div class="col-12">
                Firefly III: {{ version }}<br>
                API: {{ api }}<br>
                OS: {{ os }}
              </div>
            </div>
          </q-card-section>
        </q-card>
      </div>
    </div>

    <div class="row q-mx-md">
      <div class="col-xl-4 col-lg-6 col-md-12 q-pa-xs">
        <q-card bordered>
          <q-card-section>
            <div class="text-h6">Is demo site?
              <span class="text-secondary" v-if="true === isOk.is_demo_site"><span
                class="far fa-check-circle"></span></span>
              <span class="text-blue" v-if="true === isLoading.is_demo_site"><span
                class="fas fa-spinner fa-spin"></span></span>
              <span class="text-red" v-if="true === isFailure.is_demo_site"><span
                class="fas fa-skull-crossbones"></span> <small>Please refresh the page...</small></span>
            </div>
          </q-card-section>
          <q-card-section>
            <q-checkbox v-model="isDemoSite" label="Is Demo Site?"/>
          </q-card-section>
        </q-card>
      </div>

      <div class="col-xl-4 col-lg-6 col-md-12 q-pa-xs">
        <q-card bordered>
          <q-card-section>
            <div class="text-h6">Single user mode?
              <span class="text-secondary" v-if="true === isOk.single_user_mode"><span
                class="far fa-check-circle"></span></span>
              <span class="text-blue" v-if="true === isLoading.single_user_mode"><span
                class="fas fa-spinner fa-spin"></span></span>
              <span class="text-red" v-if="true === isFailure.single_user_mode"><span
                class="fas fa-skull-crossbones"></span> <small>Please refresh the page...</small></span>
            </div>
          </q-card-section>
          <q-card-section>
            <q-checkbox v-model="singleUserMode" label="Single user mode?"/>
          </q-card-section>
        </q-card>
      </div>

      <div class="col-xl-4 col-lg-6 col-md-12 q-pa-xs">
        <q-card bordered>
          <q-card-section>
            <div class="text-h6">Check for updates?
              <span class="text-secondary" v-if="true === isOk.update_check"><span
                class="far fa-check-circle"></span></span>
              <span class="text-blue" v-if="true === isLoading.update_check"><span
                class="fas fa-spinner fa-spin"></span></span>
              <span class="text-red" v-if="true === isFailure.update_check"><span
                class="fas fa-skull-crossbones"></span> <small>Please refresh the page...</small></span>
            </div>
          </q-card-section>
          <q-card-section>
            <q-select
              bottom-slots
              outlined
              v-model="permissionUpdateCheck" emit-value
              map-options :options="permissions" label="Check for updates"/>
          </q-card-section>
        </q-card>
      </div>
    </div>

  </q-page>
</template>

<script>
import About from "../../api/system/about";
import Configuration from "../../api/system/configuration";

export default {
  name: 'Index',
  created() {
    this.getInfo();
  },
  mounted() {
    this.isOk = {
      is_demo_site: true,
      single_user_mode: true,
      update_check: true,
    };
    this.isLoading = {
      is_demo_site: false,
      single_user_mode: false,
      update_check: false,
    };
    this.isFailure = {
      is_demo_site: false,
      single_user_mode: false,
      update_check: false,
    };
  },
  watch: {
    // todo these methods PUT on the first load, but shouldn't.
    isDemoSite: function (newValue, oldValue) {
      if (oldValue !== newValue) {
        let value = newValue;
        (new Configuration()).put('configuration.is_demo_site', {value});
      }
    },
    singleUserMode: function (newValue, oldValue) {
      if (oldValue !== newValue) {
        let value = newValue;
        (new Configuration()).put('configuration.single_user_mode', {value});
      }
    },
    permissionUpdateCheck: function (newValue, oldValue) {
      if (oldValue !== newValue) {
        let value = newValue;
        (new Configuration()).put('configuration.permission_update_check', {value});
      }
    }
  },
  data() {
    return {
      version: '',
      api: '',
      os: '',

      // settings
      isDemoSite: false,
      singleUserMode: true,
      permissionUpdateCheck: -1,

      // options
      permissions: [
        {value: -1, label: 'Ask me later'},
        {value: 0, label: 'Lol no'},
        {value: 1, label: 'Yes plz'},
      ],

      // info for live update:
      isOk: {},
      isLoading: {},
      isFailure: {},
    }
  },
  methods: {
    getInfo: function () {
      (new About).list().then((response) => {
        this.version = response.data.data.version;
        this.api = response.data.data.api_version;
        this.os = response.data.data.os + ' with php ' + response.data.data.php_version;
      });
      (new Configuration).get('configuration.is_demo_site').then((response) => {
        this.isDemoSite = response.data.data.value;
      });
      (new Configuration).get('configuration.single_user_mode').then((response) => {
        this.singleUserMode = response.data.data.value;
      });
      (new Configuration).get('configuration.permission_update_check').then((response) => {
        this.permissionUpdateCheck = response.data.data.value;
      });

    }
  },
}
</script>
