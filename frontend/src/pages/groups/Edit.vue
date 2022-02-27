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
            <div class="text-h6">Edit group</div>
          </q-card-section>
          <q-card-section>
            <div class="row">
              <div class="col-12 q-mb-xs">
                <q-input
                  :error-message="submissionErrors.title"
                  :error="hasSubmissionErrors.title"
                  bottom-slots :disable="disabledInput" type="text" clearable v-model="title" :label="$t('form.title')"
                  outlined/>
              </div>
            </div>
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
                <q-btn :disable="disabledInput" color="primary" label="Update" @click="submitGroup"/>
              </div>
            </div>
            <div class="row">
              <div class="col-12 text-right">
                <q-checkbox :disable="disabledInput" v-model="doReturnHere" left-label label="Return here"/>
              </div>
            </div>
          </q-card-section>
        </q-card>
      </div>
    </div>

  </q-page>
</template>

<script>
import Get from "../../api/groups/get";
import Put from "../../api/groups/put";

export default {
  name: "Edit",
  data() {
    return {
      submissionErrors: {},
      hasSubmissionErrors: {},
      submitting: false,
      doReturnHere: false,
      doResetForm: false,
      errorMessage: '',
      type: '',
      // group fields:
      id: 0,
      title: '',
    }
  },
  computed: {
    disabledInput: function () {
      return this.submitting;
    }
  },
  created() {
    this.id = parseInt(this.$route.params.id);
    this.collectGroup();
  },
  methods: {
    collectGroup: function() {
      let get = new Get;
      get.get(this.id).then((response) => this.parseGroup(response));
    },
    parseGroup: function(response) {
      this.title = response.data.data.attributes.title;
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
    submitGroup: function () {
      this.submitting = true;
      this.errorMessage = '';

      // reset errors:
      this.resetErrors();

      // build account array
      const submission = this.buildGroup();

      let groups = new Put();
      groups
        .post(this.id, submission)
        .catch(this.processErrors)
        .then(this.processSuccess);
    },
    buildGroup: function () {
      return {
        title: this.title,
      };
    },
    dismissBanner: function () {
      this.errorMessage = '';
    },
    processSuccess: function (response) {
      this.$store.dispatch('fireflyiii/refreshCacheKey');
      if (!response) {
        return;
      }
      this.submitting = false;
      let message = {
        level: 'success',
        text: 'Group is updated',
        show: true,
        action: {
          show: true,
          text: 'Go to group',
          link: {name: 'groups.show', params: {id: parseInt(response.data.data.id)}}
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

<style scoped>

</style>
