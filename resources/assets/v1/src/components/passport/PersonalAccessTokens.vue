<!--
  - PersonalAccessTokens.vue
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

<style scoped>
.action-link {
  cursor: pointer;
}
</style>

<template>
  <div>
    <div>
      <div class="box box-default">
        <div class="box-header">
          <h3 class="box-title">{{ $t('firefly.profile_personal_access_tokens') }}</h3>
          <a class="btn btn-default pull-right" tabindex="-1" @click="showCreateTokenForm">
            {{ $t('firefly.profile_create_new_token') }}
          </a>
        </div>

        <div class="box-body">
          <!-- No Tokens Notice -->
          <p v-if="tokens.length === 0" class="mb-0">
            {{ $t('firefly.profile_no_personal_access_token') }}
          </p>

          <!-- Personal Access Tokens -->
          <table v-if="tokens.length > 0" class="table table-responsive table-borderless mb-0">
            <caption style="display:none;">{{ $t('firefly.profile_personal_access_tokens') }}</caption>
            <thead>
            <tr>
              <th scope="col">{{ $t('firefly.name') }}</th>
              <th scope="col">{{ $t('firefly.expires_at') }}</th>
              <th scope="col"></th>
            </tr>
            </thead>

            <tbody>
            <tr v-for="token in tokens">
              <!-- Client Name -->
              <td style="vertical-align: middle;">
                {{ token.name }}
              </td>
                <!-- expires at -->
              <td style="vertical-align: middle;">
                {{ new Date(token.expires_at).toLocaleString() }}
              </td>

              <!-- Delete Button -->
              <td style="vertical-align: middle;">
                <a class="action-link text-danger" @click="revoke(token)">
                  {{ $t('firefly.delete') }}
                </a>
              </td>
            </tr>
            </tbody>
          </table>
        </div>
        <div class="box-footer">
          <a class="btn btn-default pull-right" tabindex="-1" @click="showCreateTokenForm">
            {{ $t('firefly.profile_create_new_token') }}
          </a>
        </div>
      </div>
    </div>

    <!-- Create Token Modal -->
    <div id="modal-create-token" class="modal fade" role="dialog" tabindex="-1">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h4 class="modal-title">
              {{ $t('firefly.profile_create_token') }}
            </h4>

            <button aria-hidden="true" class="close" data-dismiss="modal" type="button">&times;</button>
          </div>

          <div class="modal-body">
            <!-- Form Errors -->
            <div v-if="form.errors.length > 0" class="alert alert-danger">
              <p class="mb-0"><strong>{{ $t('firefly.profile_whoops') }}</strong>
                {{ $t('firefly.profile_something_wrong') }}</p>
              <br>
              <ul>
                <li v-for="error in form.errors">
                  {{ error }}
                </li>
              </ul>
            </div>

            <!-- Create Token Form -->
            <form role="form" @submit.prevent="store">
              <!-- Name -->
              <div class="form-group row">
                <label class="col-md-4 col-form-label">{{ $t('firefly.name') }}</label>

                <div class="col-md-6">
                  <input id="create-token-name" v-model="form.name" class="form-control" name="name" type="text" spellcheck="false">
                </div>
              </div>

              <!-- Scopes -->
              <div v-if="scopes.length > 0" class="form-group row">
                <label class="col-md-4 col-form-label">{{ $t('firefly.profile_scopes') }}</label>

                <div class="col-md-6">
                  <div v-for="scope in scopes">
                    <div class="checkbox">
                      <label>
                        <input :checked="scopeIsAssigned(scope.id)"
                               type="checkbox"
                               @click="toggleScope(scope.id)">

                        {{ scope.id }}
                      </label>
                    </div>
                  </div>
                </div>
              </div>
            </form>
          </div>

          <!-- Modal Actions -->
          <div class="modal-footer">
            <button class="btn btn-secondary" data-dismiss="modal" type="button">{{ $t('firefly.close') }}</button>

            <button class="btn btn-primary" type="button" @click="store">
              Create
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Access Token Modal -->
    <div id="modal-access-token" class="modal fade" role="dialog" tabindex="-1">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h4 class="modal-title">
              {{ $t('firefly.profile_personal_access_token') }}
            </h4>

            <button aria-hidden="true" class="close" data-dismiss="modal" type="button">&times;</button>
          </div>

          <div class="modal-body">
            <p>
              {{ $t('firefly.profile_personal_access_token_explanation') }}
            </p>
            <textarea class="form-control" readonly rows="20" style="width:100%;">{{ accessToken }}</textarea>
          </div>

          <!-- Modal Actions -->
          <div class="modal-footer">
            <button class="btn btn-secondary" data-dismiss="modal" type="button">{{ $t('firefly.close') }}</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  /*
   * The component's data.
   */
  data() {
    return {
      accessToken: null,

      tokens: [],
      scopes: [],

      form: {
        name: '',
        scopes: [],
        errors: []
      }
    };
  },

  /**
   * Prepare the component (Vue 1.x).
   */
  ready() {
    this.prepareComponent();
  },

  /**
   * Prepare the component (Vue 2.x).
   */
  mounted() {
    this.prepareComponent();
  },

  methods: {
    /**
     * Prepare the component.
     */
    prepareComponent() {
      this.getTokens();
      this.getScopes();

      $('#modal-create-token').on('shown.bs.modal', () => {
        $('#create-token-name').focus();
      });
    },

    /**
     * Get all of the personal access tokens for the user.
     */
    getTokens() {
      axios.get('./oauth/personal-access-tokens')
          .then(response => {
            this.tokens = response.data;
          });
    },

    /**
     * Get all the available scopes.
     */
    getScopes() {
      axios.get('./oauth/scopes')
          .then(response => {
            this.scopes = response.data;
          });
    },

    /**
     * Show the form for creating new tokens.
     */
    showCreateTokenForm() {
      $('#modal-create-token').modal('show');
    },

    /**
     * Create a new personal access token.
     */
    store() {
      this.accessToken = null;

      this.form.errors = [];

      axios.post('./oauth/personal-access-tokens', this.form)
          .then(response => {
            this.form.name = '';
            this.form.scopes = [];
            this.form.errors = [];

            this.tokens.push(response.data.token);

            this.showAccessToken(response.data.accessToken);
          })
          .catch(error => {
            if (typeof error.response.data === 'object') {
              this.form.errors = _.flatten(_.toArray(error.response.data.errors));
            } else {
              this.form.errors = ['Something went wrong. Please try again.'];
            }
          });
    },

    /**
     * Toggle the given scope in the list of assigned scopes.
     */
    toggleScope(scope) {
      if (this.scopeIsAssigned(scope)) {
        this.form.scopes = _.reject(this.form.scopes, s => s == scope);
      } else {
        this.form.scopes.push(scope);
      }
    },

    /**
     * Determine if the given scope has been assigned to the token.
     */
    scopeIsAssigned(scope) {
      return _.indexOf(this.form.scopes, scope) >= 0;
    },

    /**
     * Show the given access token to the user.
     */
    showAccessToken(accessToken) {
      $('#modal-create-token').modal('hide');

      this.accessToken = accessToken;

      $('#modal-access-token').modal('show');
    },

    /**
     * Revoke the given token.
     */
    revoke(token) {
      axios.delete('./oauth/personal-access-tokens/' + token.id)
          .then(response => {
            this.getTokens();
          });
    }
  }
}
</script>

