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

    }
}
</script>

<style scoped>

</style>
