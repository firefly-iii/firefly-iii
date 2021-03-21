<!--
  - TransactionPiggyBank.vue
  - Copyright (c) 2021 james@firefly-iii.org
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
  <div class="form-group">
    <div class="text-xs d-none d-lg-block d-xl-block">
      {{ $t('firefly.piggy_bank') }}
    </div>
    <div class="input-group">
      <select
          ref="piggy_bank_id"
          v-model="piggy_bank_id"
          :class="errors.length > 0 ? 'form-control is-invalid' : 'form-control'"
          :title="$t('firefly.piggy_bank')"
          autocomplete="off"
          name="piggy_bank_id[]"
          v-on:submit.prevent
      >
        <optgroup v-for="group in this.piggyGroups" v-bind:key="group.title" :label="group.title">
          <option v-for="piggy in group.piggies" :label="piggy.name_with_balance" :value="piggy.id">{{ piggy.name_with_balance }}</option>
        </optgroup>

      </select>
      <!--
      <span v-for="group in this.piggyList">"{{ group.title }}"<br>

      </span>
      -->
    </div>
    <span v-if="errors.length > 0">
      <span v-for="error in errors" class="text-danger small">{{ error }}<br/></span>
    </span>
  </div>
</template>

<script>

export default {
  props: ['index', 'value', 'errors'],
  name: "TransactionPiggyBank",
  data() {
    return {
      piggyGroups: [],
      piggyList: {},
      piggy_bank_id: this.value,
      emitEvent: true
    }
  },
  created() {
    this.collectData();
  },
  methods: {
    collectData() {
      // add empty group:
      this.piggyGroups.push(
          {
            id: 0,
            title: this.$t('firefly.default_group_title_name'),
            piggies: []
          }
      );


      // empty piggy list:
      // this.piggyList['0'] = {
      //   title: this.$t('firefly.default_group_title_name'),
      //   piggies: [
      //     {
      //       id: 0,
      //       name_with_balance: this.$t('firefly.no_piggy_bank'),
      //     }
      //   ]
      // };

      // this.piggyList.push(
      //     {
      //       id: 0,
      //       name_with_balance: this.$t('firefly.no_piggy_bank'),
      //     }
      // );
      this.getPiggies();
    },
    getPiggies() {
      axios.get('./api/v1/autocomplete/piggy-banks-with-balance')
          .then(response => {
                  this.parsePiggies(response.data);
                }
          );
    },
    groupExists: function (title) {
      for (let i in this.piggyGroups) {
        if (this.piggyGroups.hasOwnProperty(i) && /^0$|^[1-9]\d*$/.test(i) && i <= 4294967294) {
          let current = this.piggyGroups[i];
          if (current.title === title) {
            return true;
          }
        }
      }
      return false;
    },
    getGroupIndex: function (groupId) {
      for (let i in this.piggyGroups) {
        if (this.piggyGroups.hasOwnProperty(i) && /^0$|^[1-9]\d*$/.test(i) && i <= 4294967294) {
          let current = this.piggyGroups[i];
          if (current.id === groupId) {
            return i;
          }
        }
      }
      return 0;
    },
    parsePiggies(data) {
      for (let key in data) {
        if (data.hasOwnProperty(key) && /^0$|^[1-9]\d*$/.test(key) && key <= 4294967294) {
          let current = data[key];
          let groupId = current.object_group_id ?? '0';
          if ('0' !== groupId) {
            if (this.groupExists(current.object_group_title)) {
              let currentGroup = this.getGroupIndex(parseInt(current.object_group_id));
              this.piggyGroups[currentGroup].piggies.push(current);
            }

            if (!this.groupExists(current.object_group_title)) {
              this.piggyGroups.push(
                  {
                    id: parseInt(current.object_group_id),
                    title: current.object_group_title,
                    piggies: [current]
                  }
              );
            }
          }
          if ('0' === groupId) {
            this.piggyGroups[0].piggies.push(current);
          }

          // //console.log('group id is ' + groupId);
          // if ('0' !== groupId) {
          //   this.piggyList[groupId] = this.piggyList[groupId] ? this.piggyList[groupId] : {title: current.object_group_title, piggies: []};
          // }
          // this.piggyList[groupId].piggies.push(
          //     {
          //       id: parseInt(current.id),
          //       name_with_balance: current.name_with_balance
          //     }
          // );
        }
      }

      //console.log(this.piggyList);
    },
  },
  watch: {
    value: function (value) {
      this.emitEvent = false;
      this.piggy_bank_id = value;
    },
    piggy_bank_id: function (value) {
      if (true === this.emitEvent) {
        this.$emit('set-field', {field: 'piggy_bank_id', index: this.index, value: value});
      }
      this.emitEvent = true;
    }
  }
}
</script>
