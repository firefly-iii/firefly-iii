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

</template>

<script>
import Title from "../form/Title";
import WebhookTrigger from "../form/WebhookTrigger";
import WebhookResponse from "../form/WebhookResponse";
import WebhookDelivery from "../form/WebhookDelivery";
import URL from "../form/URL";
import Checkbox from "../form/Checkbox";

export default {
  name: "Create",
  components: {URL, Title, WebhookTrigger, WebhookResponse, WebhookDelivery, Checkbox},
  data() {
    return {
      error_message: '',
      success_message: '',
      title: '',
      triggers: ["STORE_TRANSACTION"],
      responses: "RELEVANT",
      deliveries: "JSON",
      active: true,
      url: '',
      errors: {
        title: [],
        triggers: [],
        responses: [],
        deliveries: [],
        url: [],
        active: []
      }
    };
  },
  methods: {

  },
}
</script>
