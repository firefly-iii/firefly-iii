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
        <div class="row">
            <div class="col-lg-12 col-md-12 col-sm-12">
                <div class="box">
                    <div class="box-header with-border">
                        <h3 class="box-title">
                            {{ $t('firefly.administrations_index_menu') }}
                        </h3>
                    </div>
                    <div class="box-body">
                        {{ $t('firefly.temp_administrations_introduction') }}
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-12 col-md-12 col-sm-12">
                <div class="box">
                    <div class="box-header with-border">
                        <h3 class="box-title">
                            {{ $t('firefly.table') }}
                        </h3>
                    </div>
                    <div class="box-body no-padding">
                        <table class="table table-responsive table-hover" v-if="administrations.length > 0"
                               aria-label="A table.">
                            <thead>
                            <tr>
                                <th>{{ $t('list.title') }}</th>
                                <th>{{ $t('list.native_currency') }}</th>
                                <th class="hidden-sm hidden-xs">&nbsp;</th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr v-for="administration in administrations" :key="administration.id">
                                <td>
                                    <span v-text="administration.title"></span>
                                </td>
                                <td>
                                    <span v-text="administration.currency_name"></span> (<span v-text="administration.currency_code"></span>)
                                </td>
                                <td class="hidden-sm hidden-xs">
                                    <div class="btn-group btn-group-xs pull-right">
                                        <button type="button" class="btn btn-default dropdown-toggle"
                                                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                            {{ $t('firefly.actions') }} <span class="caret"></span></button>
                                        <ul class="dropdown-menu dropdown-menu-right" role="menu">
                                            <li><a :href="'./administrations/edit/' + administration.id"><span class="fa fa-fw fa-pencil"></span>
                                                {{ $t('firefly.edit') }}</a></li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: "Index",
    data() {
        return {
            administrations: [],
        };
    },
    mounted() {
        this.getAdministrations();
    },
    methods: {
        getAdministrations: function () {
            this.administrations = [];
            this.downloadAdministrations(1);
        },

        downloadAdministrations: function (page) {
            axios.get("./api/v1/user-groups?page=" + page).then((response) => {
                for (let i in response.data.data) {
                    if (response.data.data.hasOwnProperty(i)) {
                        let current = response.data.data[i];
                        let administration = {
                            id: current.id,
                            title: current.attributes.title,
                            currency_code: current.attributes.native_currency_code,
                            currency_name: current.attributes.native_currency_name,
                        };
                        this.administrations.push(administration);
                    }
                }

                if (response.data.meta.pagination.current_page < response.data.meta.pagination.total_pages) {
                    this.downloadAdministrations(response.data.meta.pagination.current_page + 1);
                }
            });
        },
    }
}
</script>

<style scoped>

</style>
