/*
 * oauth.js
 * Copyright (c) 2026 james@firefly-iii.org
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

import '../../boot/bootstrap.js';
import sidebar from '../../pages/shared/sidebar.js';
import dates from '../shared/dates.js';
import i18next from "i18next";

let index = function () {
    return {
        clients: [],
        i18next: null,

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
        },

        accessToken: null,

        tokens: [],
        scopes: [],

        form: {
            name: '',
            scopes: [],
            errors: []
        },
        // showCreateTokenForm



        init() {
            this.i18next = i18next;
            this.getClients();
            this.getTokens();

            $('#modal-create-token').on('shown.bs.modal', () => {
                $('#create-token-name').focus();
            });
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

            const tokenBox = document.getElementById("token_box");
            tokenBox.onfocus = function () {
                tokenBox.select();

                // Work around Chrome's little problem
                tokenBox.onmouseup = function () {
                    // Prevent further mouseup intervention
                    tokenBox.onmouseup = null;
                    return false;
                };
            };
        },
        /**
         * Get all of the personal access tokens for the user.
         */
        getTokens() {
            console.log('getTokens()');
            axios.get('./oauth/personal-access-tokens')
                .then(response => {
                    console.log(response.data);
                    this.tokens = response.data;
                });
        },

        /**
         * Show the form for creating new tokens.
         */
        showCreateTokenForm() {
            console.log('showCreateTokenForm()');
            $('#modal-create-token').modal('show');
        },

        /**
         * Create a new personal access token.
         */
        storePat() {
            console.log('storePat()');
            this.accessToken = null;

            this.form.errors = [];

            axios.post('./oauth/personal-access-tokens', this.form)
                .then(response => {
                    console.log('Successful POST new token, reset form content.');
                    this.form.name = '';
                    this.form.scopes = [];
                    this.form.errors = [];

                    this.getTokens();
                    this.showAccessToken(response.data.accessToken);

                })
                .catch(error => {
                    console.warn('Bad POST new token, show error.');
                    if (typeof error.response.data === 'object') {
                        this.form.errors = _.flatten(_.toArray(error.response.data.errors));
                    } else {
                        this.form.errors = ['Something went wrong. Please try again.'];
                    }
                });
        },


        /**
         * Show the given access token to the user.
         */
        showAccessToken(accessToken) {
            console.log('showAccessToken');
            $('#modal-create-token').modal('hide');

            this.accessToken = accessToken;

            $('#modal-access-token').modal('show');
        },
        getClients() {
            axios.get('./oauth/clients')
                .then(response => {
                    console.log(response.data);
                    this.clients = response.data;
                });
        },
        showCreateClientForm() {
            $('#modal-create-client').modal('show');
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
                        for (const [key, value] of Object.entries(error.response.data.errors)) {
                            console.log(`${key}: ${value}`);
                            form.errors.push(value);
                        }
                    } else {
                        form.errors = ['Something went wrong. Please try again.'];
                    }
                    console.log(form.errors);
                });
        },
        /**
         * Revoke the given token.
         */
        revoke(token) {
            axios.delete('./oauth/personal-access-tokens/' + token.id)
                .then(response => {
                    this.getTokens();
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
    }
};


const comps = {
    index,
    sidebar,
    dates
};

function loadPage(comps) {
    console.log('loadPage');
    Object.keys(comps).forEach(comp => {
        let data = comps[comp]();
        Alpine.data(comp, () => data);
        console.log(comp);
    });
    Alpine.start();
}

// wait for load until bootstrapped event is received.
document.addEventListener('firefly-iii-bootstrapped', () => {
    console.log('Loaded through event listener.');
    loadPage(comps);
});
// or is bootstrapped before event is triggered.
if (window.bootstrapped) {
    console.log('Loaded through window variable.');
    loadPage(comps);
}
