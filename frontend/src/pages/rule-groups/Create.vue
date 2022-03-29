<!--
  - Create.vue
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
    <div class="row q-mx-md">
      <div class="col-12">
        <q-banner inline-actions rounded class="bg-orange text-white" v-if="'' !== errorMessage">
          {{ errorMessage }}
          <template v-slot:action>
            <q-btn flat @click="dismissBanner" label="Dismiss"/>
          </template>
        </q-banner>
      </div>
    </div>
    <div class="row q-mx-md q-mt-md">
      <div class="col-12">
        <q-card bordered>
          <q-card-section>
            <div class="text-h6">Info for new rule group</div>
          </q-card-section>
          <q-card-section>
                <q-input
                  :error-message="submissionErrors.title"
                  :error="hasSubmissionErrors.title"
                  bottom-slots :disable="disabledInput" type="text" clearable v-model="title" :label="$t('form.title')"
                  outlined/>
          </q-card-section>
        </q-card>
      </div>
    </div>

    <div class="row q-mx-md">
      <div class="col-12">
        <q-card class="q-mt-xs">
          <q-card-section>
            <div class="row">
              <div class="col-12 text-right">
                <q-btn :disable="disabledInput" color="primary" label="Submit" @click="submitRuleGroup"/>
              </div>
            </div>
            <div class="row">
              <div class="col-12 text-right">
                <q-checkbox :disable="disabledInput" v-model="doReturnHere" left-label
                            label="Return here to create another one"/>
                <br/>
                <q-checkbox v-model="doResetForm" left-label :disable="!doReturnHere || disabledInput"
                            label="Reset form after submission"/>
              </div>
            </div>
          </q-card-section>
        </q-card>
      </div>
    </div>

  </q-page>
</template>

<script>
import Post from "../../api/rule-groups/post";
import {mapGetters} from "vuex";
import {getCacheKey} from "../../store/fireflyiii/getters";

export default {
  name: 'Create',
  data() {
    return {
      submissionErrors: {},
      hasSubmissionErrors: {},
      submitting: false,
      doReturnHere: false,
      doResetForm: false,
      errorMessage: '',

      // rule group fields:
      title: '',
    }
  },
  computed: {
    ...mapGetters('fireflyiii', ['getCacheKey']),
    disabledInput: function () {
      return this.submitting;
    }
  },
  created() {
    this.resetForm();
  },
  methods: {
    resetForm: function () {
      this.title = '';
      this.resetErrors();

    },
    resetErrors: function () {
      this.submissionErrors =
        {
          title: '',
        };
      this.hasSubmissionErrors = {
        title: false,
      };
    },
    submitRuleGroup: function () {
      this.submitting = true;
      this.errorMessage = '';

      // reset errors:
      this.resetErrors();

      // build category array
      const submission = this.buildRuleGroup();

      (new Post())
        .post(submission)
        .catch(this.processErrors)
        .then(this.processSuccess);
    },
    buildRuleGroup: function () {
      return {
        title: this.title,
      };
    },
    dismissBanner: function () {
      this.errorMessage = '';
    },
    processSuccess: function (response) {
      if (!response) {
        return;
      }
      this.submitting = false;
      let message = {
        level: 'success',
        text: 'I am new rule group',
        show: true,
        action: {
          show: true,
          text: 'Go to piggy',
          link: {name: 'rule-groups.show', params: {id: parseInt(response.data.data.id)}}
        }
      };
      // store flash
      this.$q.localStorage.set('flash', message);
      if (this.doReturnHere) {
        window.dispatchEvent(new CustomEvent('flash', {
          detail: {
            flash: this.$q.localStorage.getItem('flash')
          }
        }));
      }
      if (!this.doReturnHere) {
        // return to previous page.
        this.$router.go(-1);
      }

    },
    processErrors: function (error) {
      if (error.response) {
        let errors = error.response.data; // => the response payload
        this.errorMessage = errors.message;
        console.log(errors);
        for (let i in errors.errors) {
          if (errors.errors.hasOwnProperty(i)) {
            this.submissionErrors[i] = errors.errors[i][0];
            this.hasSubmissionErrors[i] = true;
          }
        }
      }
      this.submitting = false;
    },
  }
}
</script>
