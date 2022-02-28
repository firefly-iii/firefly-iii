<!--
  - Alert.vue
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
  <div class="q-ma-md" v-if="showAlert">
    <div class="row">
      <div class="col-12">
        <q-banner :class="alertClass" inline-actions>
          {{ message }}
          <template v-slot:action>
            <q-btn flat @click="dismissBanner" color="white" label="Dismiss"/>
            <q-btn flat color="white" v-if="showAction" :to="actionLink" :label="actionText"/>
          </template>
        </q-banner>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: "Alert",
  data() {
    return {
      showAlert: false,
      alertClass: 'bg-green text-white',
      message: '',
      showAction: false,
      actionText: '',
      actionLink: {}
    }
  },
  watch: {
    '$route': function () {
      this.checkAlert();
    }
  },
  mounted() {
    this.checkAlert();
    window.addEventListener('flash', (event) => {
      this.renderAlert(event.detail.flash);
    });
  },
  methods: {
    checkAlert: function () {
      let alert = this.$q.localStorage.getItem('flash');
      if (alert) {
        this.renderAlert(alert);
      }
      if (false === alert) {
        this.showAlert = false;
      }
    },
    renderAlert: function (alert) {
      // show?
      this.showAlert = alert.show ?? false;

      // get class
      let level = alert.level ?? 'unknown';
      this.alertClass = 'bg-green text-white';
      if ('warning' === level) {
        // untested yet.
        this.alertClass = 'bg-orange text-white';
      }

      // render message:
      this.message = alert.text ?? '';
      let action = alert.action ?? {};
      if (true === action.show) {
        this.showAction = true;
        this.actionText = action.text;
        this.actionLink = action.link;
      }
      this.$q.localStorage.set('flash', false);
    },
    dismissBanner: function() {
      this.showAlert = false;
    }
  }
}
</script>

<style scoped>

</style>
