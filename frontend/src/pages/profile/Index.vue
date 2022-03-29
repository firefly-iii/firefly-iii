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
    <!-- TODO Authentication different page -->

    <div class="row q-mx-md">
      <div class="col-xl-4 col-lg-6 col-md-12 q-pa-xs">
        <q-card bordered>
          <q-card-section>
            <div class="text-h6">Email address</div>
          </q-card-section>
          <q-card-section>
            <q-input outlined type="email" required v-model="emailAddress" label="Email address">
              <template v-slot:prepend>
                <q-icon name="fas fa-envelope"/>
              </template>
            </q-input>
            <p class="text-primary">
              If you change your email address you will be logged out. You must confirm your address change before you can login again.
            </p>
          </q-card-section>
          <q-card-actions v-if="emailTouched">
            <q-btn flat @click="confirmAddressChange">Change address</q-btn>
          </q-card-actions>
        </q-card>
      </div>
      <!--
      <div class="col-xl-4 col-lg-6 col-md-12 q-pa-xs">
        <q-card bordered>
          <q-card-section>
            <div class="text-h6">Password</div>
          </q-card-section>
          <q-card-section>
            <p>
              (input) (input)
            </p>
            <p class="text-primary">
              Change password instructions here. Also needs logout. Button does not work.
            </p>
          </q-card-section>
        </q-card>
      </div>
      -->

      <!--
      <div class="col-xl-4 col-lg-6 col-md-12 q-pa-xs">
        <q-card bordered>
          <q-card-section>
            <div class="text-h6">2FA</div>
          </q-card-section>
          <q-card-section>
            <p class="text-primary">
              Here
            </p>
          </q-card-section>
        </q-card>
      </div>
      -->

      <!--
      <div class="col-xl-4 col-lg-6 col-md-12 q-pa-xs">
        <q-card bordered>
          <q-card-section>
            <div class="text-h6">Session management</div>
          </q-card-section>
          <q-card-section>
            <p class="text-primary">
              Explanation here
            </p>
          </q-card-section>
          <q-card-actions>
            Logout one / Logout all
          </q-card-actions>
        </q-card>
      </div>
    -->
    </div>

    <q-page-sticky position="bottom-right" :offset="[18, 18]">
      <q-fab
        label="Actions"
        square
        vertical-actions-align="right"
        label-position="left"
        color="green"
        icon="fas fa-chevron-up"
        direction="up">
        <q-fab-action color="primary" square :to="{ name: 'profile.data' }" icon="fas fa-database" label="Manage data"/>
      </q-fab>
    </q-page-sticky>
  </q-page>
</template>

<script>
import AboutUser from "../../api/system/user";

export default {
  name: 'Index',
  data() {
    return {
      tab: 'mails',
      id: 0,
      emailAddress: '',
      emailOriginal: '',
      emailTouched: false,
    }
  },
  watch: {
    emailAddress: function (value) {
      this.emailTouched = false;
      if (this.emailOriginal !== value) {
        this.emailTouched = true;
      }
    }
  },
  created() {
    this.getUserInfo();
  },
  methods: {
    getUserInfo: function () {
      (new AboutUser).get().then((response) => {
        this.emailAddress = response.data.data.attributes.email;
        this.emailOriginal = response.data.data.attributes.email;
        this.id = parseInt(response.data.data.id);
      });
    },
    confirmAddressChange: function () {
      this.$q.dialog({
                       title: 'Confirm',
                       message: 'Are you sure?',
                       cancel: true,
                       persistent: false
                     }).onOk(() => {
        this.submitAddressChange();
      }).onCancel(() => {
        // console.log('>>>> Cancel')
      }).onDismiss(() => {
        // console.log('I am triggered on both OK and Cancel')
      })
    },
    submitAddressChange: function () {
      (new AboutUser).put(this.id, {email: this.emailAddress})
      .then((response) =>  {
        (new AboutUser).logout();
      });
    }
  },
}
</script>
