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
            <div class="text-h6">Info for new rule</div>
          </q-card-section>
          <q-card-section>
            <q-input
              :error-message="submissionErrors.title"
              :error="hasSubmissionErrors.title"
              bottom-slots :disable="disabledInput" type="text" clearable v-model="title" :label="$t('form.title')"
              outlined/>

            <q-select
              :error-message="submissionErrors.rule_group_id"
              :error="hasSubmissionErrors.rule_group_id"
              bottom-slots
              :disable="disabledInput"
              outlined
              dense
              v-model="rule_group_id"
              class="q-pr-xs"
              map-options :options="ruleGroups" label="Rule group"/>

            <q-select
              :error-message="submissionErrors.trigger"
              :error="hasSubmissionErrors.trigger"
              bottom-slots
              :disable="disabledInput"
              outlined
              dense
              emit-value
              v-model="trigger"
              class="q-pr-xs"
              map-options :options="initialTriggers" label="What fires a rule?"/>
          </q-card-section>
        </q-card>
      </div>
    </div>

    <div class="row q-mx-md q-mt-md">
      <div class="col-12">
        <q-card bordered>
          <q-card-section>
            <div class="text-h6">Triggers</div>
          </q-card-section>
          <q-card-section>
            <div class="row">
              <div class="col">
                <strong>Trigger</strong>
              </div>
              <div class="col">
                <strong>Trigger on value</strong>
              </div>
              <div class="col">
                <strong>Active?</strong>
              </div>
              <div class="col">
                <strong>Stop processing after a hit</strong>
              </div>
              <div class="col">
                del
              </div>
            </div>
            <div v-for="(trigger, index) in triggers" class="row" :key="index">
              <div class="col">
                <q-select
                  :error-message="submissionErrors.triggers[index].type"
                  :error="hasSubmissionErrors.triggers[index].type"
                  bottom-slots
                  :disable="disabledInput"
                  outlined
                  dense
                  v-model="trigger.type"
                  class="q-pr-xs"
                  map-options :options="availableTriggers" label="Trigger type"/>

              </div>
              <div class="col">
                <q-input
                  :error-message="submissionErrors.triggers[index].value"
                  :error="hasSubmissionErrors.triggers[index].value"
                  bottom-slots
                  dense
                  :disable="disabledInput"
                  v-if="trigger.type.needs_context"
                  type="text" clearable v-model="trigger.value" label="Trigger value"
                  outlined/>
              </div>
              <div class="col">
                <q-checkbox v-model="trigger.active"/>
              </div>
              <div class="col">
                <q-checkbox v-model="trigger.stop_processing"/>
              </div>
              <div class="col">
                <q-btn color="secondary" @click="removeTrigger(index)">Del</q-btn>
              </div>
            </div>
          </q-card-section>
          <q-card-actions>
            <q-btn color="primary" @click="addTrigger">Add trigger</q-btn>
          </q-card-actions>
        </q-card>
      </div>
    </div>

    <div class="row q-mx-md q-mt-md">
      <div class="col-12">
        <q-card bordered>
          <q-card-section>
            <div class="text-h6">Actions</div>
          </q-card-section>
          <q-card-section>
            <div class="row">
              <div class="col">
                <strong>Action</strong>
              </div>
              <div class="col">
                <strong>Value</strong>
              </div>
              <div class="col">
                <strong>Active?</strong>
              </div>
              <div class="col">
                <strong>Stop processing other actions</strong>
              </div>
              <div class="col">
                del
              </div>
            </div>
            <div v-for="(action, index) in actions" class="row" :key="index">
              <div class="col">
                <q-select
                  :error-message="submissionErrors.actions[index].type"
                  :error="hasSubmissionErrors.actions[index].type"
                  bottom-slots
                  :disable="disabledInput"
                  outlined
                  dense
                  v-model="action.type"
                  class="q-pr-xs"
                  map-options :options="availableActions" label="Action type"/>

              </div>
              <div class="col">
                <q-input
                  :error-message="submissionErrors.actions[index].value"
                  :error="hasSubmissionErrors.actions[index].value"
                  bottom-slots
                  dense
                  :disable="disabledInput"
                  v-if="action.type.needs_context"
                  type="text" clearable v-model="action.value" label="Action value"
                  outlined/>
              </div>
              <div class="col">
                <q-checkbox v-model="action.active"/>
              </div>
              <div class="col">
                <q-checkbox v-model="action.stop_processing"/>
              </div>
              <div class="col">
                <q-btn color="secondary" @click="removeAction(index)">Del</q-btn>
              </div>
            </div>
          </q-card-section>
          <q-card-actions>
            <q-btn color="primary" @click="addAction">Add action</q-btn>
          </q-card-actions>
        </q-card>
      </div>
    </div>

    <div class="row q-mx-md">
      <div class="col-12">
        <q-card class="q-mt-xs">
          <q-card-section>
            <div class="row">
              <div class="col-12 text-right">
                <q-btn :disable="disabledInput" color="primary" label="Submit" @click="submitRule"/>
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
import Post from "../../api/rules/post";
import {mapGetters} from "vuex";
import {getCacheKey} from "../../store/fireflyiii/getters";
import Configuration from "../../api/system/configuration";
import List from "../../api/rule-groups/list";

export default {
  name: 'Create',
  data() {
    return {
      submissionErrors: {
        triggers: [],
        actions: [],
      },
      hasSubmissionErrors: {
        triggers: [],
        actions: []
      },
      submitting: false,
      doReturnHere: false,
      doResetForm: false,
      errorMessage: '',

      // rule settings things:
      ruleGroups: [],
      availableTriggers: [],
      availableActions: [],
      initialTriggers: [],

      // rule group fields:
      title: '',
      rule_group_id: null,
      trigger: 'store-journal',

      triggers: [],
      actions: []
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
    this.getRuleGroups();
    this.getRuleTriggers();
    this.getRuleActions();
  },
  methods: {
    addTrigger: function () {
      this.triggers.push(
        this.getDefaultTrigger()
      );
      this.submissionErrors.triggers.push(
        this.getDefaultTriggerError()
      );
      this.hasSubmissionErrors.triggers.push(
        this.getDefaultHasTriggerError()
      );
    },
    addAction: function () {
      this.actions.push(
        this.getDefaultAction()
      );
      this.submissionErrors.actions.push(
        this.getDefaultActionError()
      );
      this.hasSubmissionErrors.actions.push(
        this.getDefaultHasActionError()
      );
    },
    getDefaultTriggerError: function () {
      return {
        type: '',
        value: '',
        stop_processing: '',
        active: '',
      };
    },
    getDefaultActionError: function () {
      return {
        type: '',
        value: '',
        stop_processing: '',
        active: '',
      };
    },
    getDefaultHasTriggerError: function () {
      return {
        type: false,
        value: false,
        stop_processing: false,
        active: false,
      };
    },
    getDefaultHasActionError: function () {
      return {
        type: false,
        value: false,
        stop_processing: false,
        active: false,
      };
    },

    removeTrigger: function (index) {
      this.triggers.splice(index, 1);
      this.submissionErrors.triggers.splice(index, 1);
      this.hasSubmissionErrors.triggers.splice(index, 1);
    },
    removeAction: function (index) {
      this.actions.splice(index, 1);
      this.submissionErrors.actions.splice(index, 1);
      this.hasSubmissionErrors.actions.splice(index, 1);
    },
    getDefaultTrigger: function () {
      return {
        type: {
          value: 'description_is',
          needs_context: true,
          label: this.$t('firefly.rule_trigger_description_is_choice')
        },
        value: '',
        stop_processing: false,
        active: true
      };
    },
    getDefaultAction: function () {
      return {
        type: {
          value: 'add_tag',
          needs_context: true,
          label: this.$t('firefly.rule_action_add_tag_choice')
        },
        value: '',
        stop_processing: false,
        active: true
      };
    },
    getRuleTriggers: function () {
      let config = new Configuration;
      config.get('firefly.search.operators').then((response) => {
        for (let i in response.data.data.value) {
          if (response.data.data.value.hasOwnProperty(i)) {
            let trigger = response.data.data.value[i];
            if (false === trigger.alias && i !== 'user_action') {
              this.availableTriggers.push(
                {
                  value: i,
                  needs_context: trigger.needs_context,
                  label: this.$t('firefly.rule_trigger_' + i + '_choice')
                }
              );
            }
          }
        }
      });
    },
    getRuleActions: function () {
      let config = new Configuration;
      config.get('firefly.rule-actions').then((response) => {
        for (let i in response.data.data.value) {
          if (response.data.data.value.hasOwnProperty(i)) {
            this.availableActions.push(
              {
                value: i,
                needs_context: false,
                label: this.$t('firefly.rule_action_' + i + '_choice')
              }
            );
          }
        }
      }).then(() => {
        // get actions that require context:
        config.get('firefly.context-rule-actions').then((response) => {
          let contextActions = response.data.data.value;
          for (let i in contextActions) {
            let current = contextActions[i];
            // find it in availableActions and set to true:
            for (let ii in this.availableActions) {
              let action = this.availableActions[ii];
              if (action.value === current) {
                this.availableActions[ii].needs_context = true;
              }
            }
          }
        });
      });
    },
    resetForm: function () {

      this.initialTriggers = [
        {
          value: 'store-journal',
          label: 'When a transaction is stored'
        },
        {
          value: 'update-journal',
          label: 'When a transaction is updated'
        },
      ]

      this.title = '';
      this.rule_group_id = null;
      this.trigger = 'store-journal';
      // add new (single) trigger:
      this.triggers.push(this.getDefaultTrigger());
      this.actions.push(this.getDefaultAction());

      this.resetErrors();

    },
    getRuleGroups: function () {
      this.getGroupPage(1);
    },
    getGroupPage: function (page) {
      let list = new List();
      list.list(page, this.getCacheKey).then((response) => {
        if (page < parseInt(response.data.meta.pagination.total_pages)) {
          this.getGroupPage(page + 1);
        }
        let groups = response.data.data;
        for (let i in groups) {
          if (groups.hasOwnProperty(i)) {
            let group = groups[i];
            this.ruleGroups.push(
              {
                value: parseInt(group.id),
                label: group.attributes.title,
              }
            )
          }
        }
      });
    },
    resetErrors: function () {
      this.submissionErrors =
        {
          title: '',
          rule_group_id: '',
          triggers: [this.getDefaultTriggerError()],
          actions: [this.getDefaultActionError()],
        };
      this.hasSubmissionErrors = {
        title: false,
        rule_group_id: false,
        triggers: [this.getDefaultHasTriggerError()],
        actions: [this.getDefaultHasActionError()],
      };
    },
    submitRule: function () {
      this.submitting = true;
      this.errorMessage = '';

      // reset errors:
      this.resetErrors();

      // build category array
      const submission = this.buildRule();

      (new Post())
        .post(submission)
        .catch(this.processErrors)
        .then(this.processSuccess);
    },
    buildRule: function () {
      let rule = {
        title: this.title,
        rule_group_id: this.rule_group_id,
        trigger: this.trigger,
        triggers: [],
        actions: [],
      };
      for (let i in this.triggers) {
        // todo leaves room for filtering.
        rule.triggers.push(
          {
            type: this.triggers[i].type.value,
            value: this.triggers[i].value,
            stop_processing: this.triggers[i].stop_processing,
            active: this.triggers[i].active,
          }
        );
      }
      for (let i in this.actions) {
        let action = this.actions[i];
        console.log(action);
        rule.actions.push(
          {
            type: this.actions[i].type.value,
            value: this.actions[i].value,
            stop_processing: this.actions[i].stop_processing,
            active: this.actions[i].active,
          }
        );
      }
      return rule;
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
        text: 'I am new rule',
        show: true,
        action: {
          show: true,
          text: 'Go to piggy',
          link: {name: 'rules.show', params: {id: parseInt(response.data.data.id)}}
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
        for (let i in errors.errors) {
          if (errors.errors.hasOwnProperty(i)) {
            let errorKey = i;
            if (errorKey.includes('.')) {
              // it's a split
              let parts = errorKey.split('.');
              let series = parts[0];
              let errorIndex = parseInt(parts[1]);
              let errorField = parts[2];
              this.submissionErrors[series][errorIndex][errorField] = errors.errors[i][0]
              this.hasSubmissionErrors[series][errorIndex][errorField] = true;
            }
            if (!errorKey.includes('.')) {
              this.submissionErrors[i] = errors.errors[i][0];
              this.hasSubmissionErrors[i] = true;
            }
          }
        }
      }
      this.submitting = false;
    },
  }
}
</script>
