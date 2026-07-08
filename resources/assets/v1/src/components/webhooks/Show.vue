<!--
  - Show.vue
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

import format from "date-fns/format";

export default {
  name: "Show",
  mounted() {
    this.getWebhook();
  },
  data() {
    return {
      title: '',

      url: '',
      id: 0,
      secret: '',
      show_secret: false,
      trigger: '',
    triggers: [],
      loading: true,
      response: '',
    responses: [],
      message_content: '',
      message_attempts: [],
      delivery: '',
    deliveries: [],
      messages: [],
      active: false,
      edit_url: '#',
      delete_url: '#',
      success_message: '',
      disabledTrigger: false
    };
  },
  methods: {

  }
}
</script>
