<!--
  - Clients.vue
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
        <div class="box box-default">
            <div class="box-header with-border">
                <h3 class="box-title">
                    {{ $t('firefly.profile_oauth_clients') }}
                </h3>
                <a class="btn btn-default pull-right" tabindex="-1" @click="showCreateClientForm">
                    {{ $t('firefly.profile_oauth_create_new_client') }}
                </a>
            </div>
            <div class="box-body">
                <!-- Current Clients -->
                <p>
                    {{ $t('firefly.profile_oauth_clients_explain') }}
                </p>
                <p>
                    {{ $t('firefly.profile_oauth_clients_external_auth') }}
                </p>
                <p v-if="clients.length === 0">
                    {{ $t('firefly.profile_oauth_no_clients') }}
                </p>
                <table v-if="clients.length > 0" class="table table-responsive table-borderless mb-0">
                    <caption>{{ $t('firefly.profile_oauth_clients_header') }}</caption>
                    <thead>
                    <tr>
                        <th class="width-30" scope="col">{{ $t('firefly.profile_oauth_client_id') }}</th>
                        <th class="width-30" scope="col">{{ $t('firefly.name') }}</th>
                        <th class="width-40" scope="col" style="text-align:right">{{ $t('form.buttons') }}</th>
                    </tr>
                    </thead>

                    <tbody>
                    <tr v-for="client in clients">
                        <!-- ID -->
                        <td style="vertical-align: middle;">
                            {{ client.id }}
                        </td>

                        <!-- Name -->
                        <td style="vertical-align: middle;">
                            {{ client.name }}
                        </td>

                        <!-- Secret -->
                        <td style="vertical-align: middle;text-align:right">
                            <div class="btn-group">
                            <a :title="$t('firefly.regenerate_secret')" class="btn btn-default btn-sm" @click="regenerateSecret(client)">
                                <em :title="$t('firefly.regenerate_secret')" class="fa fa-retweet"></em>
                                {{ $t('firefly.regenerate_secret') }}
                            </a>
                            <a class="btn btn-sm btn-default" :title="$t('firefly.edit')" tabindex="-1" @click="edit(client)">
                                <em :title="$t('firefly.edit')" class="fa fa-pencil"></em>
                                {{ $t('firefly.edit') }}
                            </a>
                            <a :title="$t('firefly.delete')" class="btn btn-sm btn-danger" @click="destroy(client)">
                                <em :title="$t('firefly.delete')" class="fa fa-trash"></em>
                                {{ $t('firefly.delete') }}
                            </a>
                            </div>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>
            <div class="box-footer">
                <a class="btn btn-default pull-right" tabindex="-1" @click="showCreateClientForm">
                    {{ $t('firefly.profile_oauth_create_new_client') }}
                </a>
            </div>
        </div>

        <!-- Create Client Modal -->
        <div id="modal-create-client" class="modal fade" role="dialog" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">
                            {{ $t('firefly.profile_oauth_create_client') }}
                        </h4>

                        <button aria-hidden="true" class="close" data-dismiss="modal" type="button">&times;</button>
                    </div>

                    <div class="modal-body">
                        <!-- Form Errors -->
                        <div v-if="createForm.errors.length > 0" class="alert alert-danger">
                            <p class="mb-0"><strong>{{ $t('firefly.profile_whoops') }}</strong> {{
                                    $t('firefly.profile_something_wrong')
                                }}</p>
                            <br>
                            <ul>
                                <li v-for="error in createForm.errors">
                                    {{ error }}
                                </li>
                            </ul>
                        </div>

                        <!-- Create Client Form -->
                        <form role="form" aria-label="form">
                            <!-- Name -->
                            <div class="form-group row">
                                <label class="col-md-3 col-form-label">{{ $t('firefly.name') }}</label>

                                <div class="col-md-9">
                                    <input id="create-client-name" v-model="createForm.name" class="form-control"
                                           spellcheck="false"
                                           type="text" @keyup.enter="store">

                                    <span class="form-text text-muted">
                              {{ $t('firefly.profile_oauth_name_help') }}
                                    </span>
                                </div>
                            </div>

                            <!-- Redirect URIs -->
                            <div class="form-group row">
                                <label class="col-md-3 col-form-label">{{
                                        $t('firefly.profile_oauth_redirect_url')
                                    }}</label>

                                <div class="col-md-9">
                                    <input v-model="createForm.redirect_uris" class="form-control" name="redirect_uris"
                                           spellcheck="false"
                                           type="text" @keyup.enter="store">

                                    <span class="form-text text-muted">
                              {{ $t('firefly.profile_oauth_redirect_url_help') }}
                                    </span>
                                </div>
                            </div>

                            <!-- Confidential -->
                            <div class="form-group row">
                                <label class="col-md-3 col-form-label">{{
                                        $t('firefly.profile_oauth_confidential')
                                    }}</label>

                                <div class="col-md-9">
                                    <div class="checkbox">
                                        <label>
                                            <input v-model="createForm.confidential" type="checkbox">
                                        </label>
                                    </div>

                                    <span class="form-text text-muted">
                    {{ $t('firefly.profile_oauth_confidential_help') }}
                  </span>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- Modal Actions -->
                    <div class="modal-footer">
                        <button class="btn btn-secondary" data-dismiss="modal" type="button">{{
                                $t('firefly.close')
                            }}
                        </button>

                        <button class="btn btn-primary" type="button" @click="store">
                            {{ $t('firefly.profile_create') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Client Modal -->
        <div id="modal-edit-client" class="modal fade" role="dialog" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">
                            {{ $t('firefly.profile_oauth_edit_client') }}
                        </h4>

                        <button aria-hidden="true" class="close" data-dismiss="modal" type="button">&times;</button>
                    </div>

                    <div class="modal-body">
                        <!-- Form Errors -->
                        <div v-if="editForm.errors.length > 0" class="alert alert-danger">
                            <p class="mb-0"><strong>{{ $t('firefly.profile_whoops') }}</strong> {{
                                    $t('firefly.profile_something_wrong')
                                }}</p>
                            <br>
                            <ul>
                                <li v-for="error in editForm.errors">
                                    {{ error }}
                                </li>
                            </ul>
                        </div>

                        <!-- Edit Client Form -->
                        <form role="form" aria-label="form">
                            <!-- Name -->
                            <div class="form-group row">
                                <label class="col-md-3 col-form-label">{{ $t('firefly.name') }}</label>

                                <div class="col-md-9">
                                    <input id="edit-client-name" v-model="editForm.name" class="form-control"
                                           spellcheck="false"
                                           type="text" @keyup.enter="update">

                                    <span class="form-text text-muted">
                                {{ $t('firefly.profile_oauth_name_help') }}
                                  </span>
                                </div>
                            </div>

                            <!-- Redirect URL -->
                            <div class="form-group row">
                                <label class="col-md-3 col-form-label">{{
                                        $t('firefly.profile_oauth_redirect_url')
                                    }}</label>

                                <div class="col-md-9">
                                    <input v-model="editForm.redirect_uris" class="form-control" name="redirect_uris"
                                           spellcheck="false"
                                           type="text" @keyup.enter="update">

                                    <span class="form-text text-muted">
                                        {{ $t('firefly.profile_oauth_redirect_url_help') }}
                                    </span>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- Modal Actions -->
                    <div class="modal-footer">
                        <button class="btn btn-secondary" data-dismiss="modal" type="button">{{
                                $t('firefly.close')
                            }}
                        </button>

                        <button class="btn btn-primary" type="button" @click="update">
                            {{ $t('firefly.profile_save_changes') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Client Secret Modal -->
        <div id="modal-client-secret" class="modal fade" role="dialog" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">
                            {{ $t('firefly.profile_oauth_client_secret_title') }}
                        </h4>

                        <button aria-hidden="true" class="close" data-dismiss="modal" type="button">&times;</button>
                    </div>

                    <div class="modal-body">
                        <p>
                            {{ $t('firefly.profile_oauth_client_secret_expl') }}
                        </p>
                        <input id="secret_box" v-model="clientSecret" class="form-control" type="text" spellcheck="false">
                    </div>

                    <!-- Modal Actions -->
                    <div class="modal-footer">
                        <button class="btn btn-secondary" data-dismiss="modal" type="button">{{
                                $t('firefly.close')
                            }}
                        </button>
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
            clients: [],

            clientSecret: null,

            createForm: {
                errors: [],
                name: '',
                redirect_uris: '',
                confidential: true
            },

            editForm: {
                errors: [],
                name: '',
                redirect_uris: ''
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
            this.getClients();

            $('#modal-create-client').on('shown.bs.modal', () => {
                $('#create-client-name').focus();
            });

            $('#modal-edit-client').on('shown.bs.modal', () => {
                $('#edit-client-name').focus();
            });
            const textBox = document.getElementById("secret_box");
            textBox.onfocus = function () {
                textBox.select();

                // Work around Chrome's little problem
                textBox.onmouseup = function () {
                    // Prevent further mouseup intervention
                    textBox.onmouseup = null;
                    return false;
                };
            };
        },

        /**
         * Get all of the OAuth clients for the user.
         */
        getClients() {
            axios.get('./oauth/clients')
                .then(response => {
                    console.log(response.data);
                    this.clients = response.data;
                });
        },

        /**
         * Show the form for creating new clients.
         */
        showCreateClientForm() {
            $('#modal-create-client').modal('show');
        },

        /**
         * Create a new OAuth client for the user.
         */
        store() {
            this.persistClient(
                'post',
                './oauth/clients',
                this.createForm,
                '#modal-create-client'
            );
        },

        /**
         * Edit the given client.
         */
        edit(client) {
            this.editForm.id = client.id;
            this.editForm.name = client.name;
            this.editForm.redirect_uris = client.redirect_uris.join(',');

            $('#modal-edit-client').modal('show');
        },

        /**
         * Update the client being edited.
         */
        update() {
            this.persistClient(
                'put',
                './oauth/clients/' + this.editForm.id,
                this.editForm,
                '#modal-edit-client'
            );
        },

        /**
         * Persist the client to storage using the given form.
         */
        persistClient(method, uri, form, modal) {
            form.errors = [];

            axios[method](uri, form)
                .then(response => {
                    this.getClients();

                    form.name = '';
                    form.redirect_uris = '';
                    form.errors = [];

                    $(modal).modal('hide');

                    if (response.data.plainSecret) {
                        this.showClientSecret(response.data.plainSecret);
                    }
                })
                .catch(error => {
                    if (typeof error.response.data === 'object') {
                        form.errors = _.flatten(_.toArray(error.response.data.errors));
                    } else {
                        form.errors = ['Something went wrong. Please try again.'];
                    }
                });
        },

        /**
         * Show the given client secret to the user.
         */
        showClientSecret(clientSecret) {
            this.clientSecret = clientSecret;

            $('#modal-client-secret').modal('show');
        },
        regenerateSecret(client) {
            axios.post('./oauth/clients/regenerate/' + client.id)
                .then(response => {
                    this.clientSecret = response.data.plainSecret;

                    $('#modal-client-secret').modal('show');
                });

        },

        /**
         * Destroy the given client.
         */
        destroy(client) {
            axios.delete('./oauth/clients/' + client.id)
                .then(response => {
                    this.getClients();
                });
        }
    }
}
</script>
