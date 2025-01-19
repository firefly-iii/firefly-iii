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
    <div>
        <form accept-charset="UTF-8" class="form-horizontal" enctype="multipart/form-data">
            <input name="_token" type="hidden" value="xxx">

            <div v-if="error_message !== ''" class="row">
                <div class="col-lg-12">
                    <div class="alert alert-danger alert-dismissible" role="alert">
                        <button class="close" data-dismiss="alert" type="button" v-bind:aria-label="$t('firefly.close')"><span
                            aria-hidden="true">&times;</span></button>
                        <strong>{{ $t("firefly.flash_error") }}</strong> {{ error_message }}
                    </div>
                </div>
            </div>

            <div v-if="success_message !== ''" class="row">
                <div class="col-lg-12">
                    <div class="alert alert-success alert-dismissible" role="alert">
                        <button class="close" data-dismiss="alert" type="button" v-bind:aria-label="$t('firefly.close')"><span
                            aria-hidden="true">&times;</span></button>
                        <strong>{{ $t("firefly.flash_success") }}</strong> <span v-html="success_message"></span>
                    </div>
                </div>
            </div>

        <div class="row">
            <div class="col-lg-12 col-md-12 col-sm-12">
                <div class="box">
                    <div class="box-header with-border">
                        <h3 class="box-title">
                            {{ $t('firefly.administrations_page_edit_sub_title_js', {title: this.pageTitle}) }}
                        </h3>
                    </div>
                    <div class="box-body">
                        {{ $t('firefly.temp_administrations_introduction') }}
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-8 col-lg-offset-2 col-md-12 col-sm-12">
                <div class="box">
                    <div class="box-header with-border">
                        <h3 class="box-title">
                            {{ $t('firefly.administrations_page_edit_sub_title_js', {title: this.pageTitle}) }}
                        </h3>
                    </div>
                    <div class="box-body">
                        <Title :value=administration.title :error="errors.title" v-on:input="administration.title = $event"></Title>
                        <UserGroupCurrency :value=administration.currency_id :error="errors.currency_id"
                                        v-on:input="administration.currency_id = $event"></UserGroupCurrency>
                    </div>
                    <div class="box-footer">
                        <div class="btn-group">
                            <button id="submitButton" ref="submitButton" class="btn btn-success" @click="submit">
                                {{ $t('firefly.submit') }}
                            </button>
                        </div>
                        <p class="text-success" v-html="success_message"></p>
                        <p class="text-danger" v-html="error_message"></p>
                    </div>
                </div>
            </div>
        </div>
        </form>
    </div>
</template>

<script>
import Title from "../form/Title.vue";
import WebhookTrigger from "../form/WebhookTrigger.vue";
import UserGroupCurrency from "../form/UserGroupCurrency.vue";

export default {
    name: "Edit",
    components: {UserGroupCurrency, WebhookTrigger, Title},
    data() {
        return {
            pageTitle: '',
            administration: {
              title: '',
                currency_id: 0,
            },
            errors: {
                title: [],
                currency_id: [],
            },
            error_message: '',
            success_message: '',
        };
    },
    mounted() {
        const page = window.location.href.split('/');
        const administrationId = parseInt(page[page.length - 1]);
        this.downloadAdministration(administrationId);
    },
    methods: {
        downloadAdministration: function (id) {
            axios.get("./api/v1/user-groups/" + id).then((response) => {
                let current = response.data.data;
                this.administration = {
                    id: current.id,
                    title: current.attributes.title,
                    currency_id: parseInt(current.attributes.native_currency_id),
                    currency_code: current.attributes.native_currency_code,
                    currency_name: current.attributes.native_currency_name,
                };
                this.pageTitle = this.administration.title;
            });
        },
        submit: function (e) {
            // reset messages
            this.error_message = '';
            this.success_message = '';
            this.errors = {
                title: [],
                currency_id: [],
            };

            // disable button
            $('#submitButton').prop("disabled", true);

            // collect data
            let data = {
                title: this.administration.title,
                native_currency_id: parseInt(this.administration.currency_id),
            };

            // post!
            axios.put('./api/v1/user-groups/' + this.administration.id, data).then((response) => {
                let administrationId = parseInt(response.data.data.id);
                window.location.href = './administrations?user_group_id=' + administrationId + '&message=updated';
            }).catch((error) => {

                this.error_message = error.response.data.message;
                this.errors.title = error.response.data.errors.title;
                this.errors.native_currency_id = error.response.data.errors.native_currency_id;

                // enable button again
                $('#submitButton').prop("disabled", false);

            });
            if (e) {
                e.preventDefault();
            }
        }
    }
}
</script>

<style scoped>

</style>
