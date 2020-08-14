<!--
  - AuthorizedClients.vue
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

<style scoped>
.action-link {
  cursor: pointer;
}
</style>

<template>
  <div>
    <div v-if="tokens.length > 0">
      <div class="box box-default">
        <div class="box-header">
          <h3 class="box-title">
            {{ $t('firefly.profile_authorized_apps') }}
          </h3>
        </div>

        <div class="box-body">
          <!-- Authorized Tokens -->
          <table class="table table-responsive table-borderless mb-0">
            <thead>
            <tr>
              <th>{{ $t('firefly.name') }}</th>
              <th>{{ $t('firefly.profile_scopes') }}</th>
              <th></th>
            </tr>
            </thead>

            <tbody>
            <tr v-for="token in tokens">
              <!-- Client Name -->
              <td style="vertical-align: middle;">
                {{ token.client.name }}
              </td>

              <!-- Scopes -->
              <td style="vertical-align: middle;">
                                    <span v-if="token.scopes.length > 0">
                                        {{ token.scopes.join(', ') }}
                                    </span>
              </td>

              <!-- Revoke Button -->
              <td style="vertical-align: middle;">
                <a class="action-link text-danger" @click="revoke(token)">
                  {{ $t('firefly.profile_revoke') }}
                </a>
              </td>
            </tr>
            </tbody>
          </table>
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
      tokens: []
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
     * Prepare the component (Vue 2.x).
     */
    prepareComponent() {
      this.getTokens();
    },

    /**
     * Get all of the authorized tokens for the user.
     */
    getTokens() {
      axios.get('/oauth/tokens')
          .then(response => {
            this.tokens = response.data;
          });
    },

    /**
     * Revoke the given token.
     */
    revoke(token) {
      axios.delete('/oauth/tokens/' + token.id)
          .then(response => {
            this.getTokens();
          });
    }
  }
}
</script>
