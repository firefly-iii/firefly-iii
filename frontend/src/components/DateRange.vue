<!--
  - DateRange.vue
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
  <div class="q-pa-xs">
    <div>
      <!-- <DatePicker v-model="range" is-range :is-dark="darkMode" :model-config="modelConfig"/> -->
      <q-date v-model="localRange" mask="YYYY-MM-DD" minimal range/>
    </div>
    <div class="q-mt-xs">
      <span class="q-mr-xs"><q-btn color="primary" label="Reset" size="sm" @click="resetRange"/></span>
      <q-btn color="primary" icon-right="fas fa-caret-down" label="Change range" size="sm"
             title="More options in preferences">
        <q-menu>
          <q-list style="min-width: 100px">
            <q-item v-for="choice in rangeChoices" v-close-popup clickable @click="setViewRange(choice)">
              <q-item-section>{{ $t('firefly.pref_' + choice.value) }}</q-item-section>
            </q-item>
          </q-list>
        </q-menu>
      </q-btn>
    </div>
  </div>
</template>

<script>
// import {mapGetters, mapMutations} from "vuex";
import {useQuasar} from 'quasar'
import Preferences from "../api/preferences";
import format from 'date-fns/format';
import {useFireflyIIIStore} from "../stores/fireflyiii";

export default {
  name: "DateRange",
  computed: {
    // ...mapGetters('fireflyiii', ['getRange']),
    // ...mapMutations('fireflyiii', ['setRange'])
  },
  data() {
    return {
      rangeChoices: [
        {value: 'last30'},
        {value: 'last7'},
        {value: 'MTD'},
        {value: '1M'},
        {value: '3M'},
        {value: '6M'},
      ],
      darkMode: false,
      range: {
        start: new Date,
        end: new Date
      },
      localRange: {
        start: new Date,
        end: new Date
      },
      modelConfig: {
        start: {
          timeAdjust: '00:00:00',
        },
        end: {
          timeAdjust: '23:59:59',
        },
      },
      store: null
    }
  },
  created() {
    this.store = useFireflyIIIStore();
    const $q = useQuasar();
    this.darkMode = $q.dark.isActive;

    this.localRange = {
      from: format(this.store.getRange.start, 'yyyy-MM-dd'),
      to: format(this.store.getRange.end, 'yyyy-MM-dd')
    };
  },
  watch: {
    localRange: function (value) {
      if (null !== value) {
        const updatedRange = {
          start: Date.parse(value.from),
          end: Date.parse(value.to)
        };
        // FIXME new store
        this.store.setRange(updatedRange);
      }
    },
  },
  mounted() {

  },
  methods: {
    resetRange: function () {
      // FIXME new store
      this.store.resetRange().then(() => {
        this.localRange = {
          from: format(this.store.getRange.start, 'yyyy-MM-dd'),
          to: format(this.store.getRange.end, 'yyyy-MM-dd')
        };
      });

    },
    setViewRange: function (value) {
      let submission = value.value;
      let preferences = new Preferences();
      preferences.postByName('viewRange', submission);
      // FIXME new store
      this.store.updateViewRange(submission);
      this.store.setDatesFromViewRange();
    },
    updateViewRange: function () {
    }
  },
  components: {},
}
</script>
