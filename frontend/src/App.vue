<!--
  - App.vue
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
  <router-view/>
</template>
<script>
import {defineComponent} from 'vue';
import Preferences from "./api/preferences";
import Prefs from "./api/v2/preferences";
import Currencies from "./api/currencies";
import {useFireflyIIIStore} from 'stores/fireflyiii'


export default defineComponent(
  {
    name: 'App',
    preFetch({store}) {
      const ffStore = useFireflyIIIStore(store);

      ffStore.refreshCacheKey();

      const getViewRange = function () {
        let pref = new Preferences();
        return pref.getByName('viewRange').then(data => {

          const viewRange = data.data.data.attributes.data;
          ffStore.updateViewRange(viewRange);
          ffStore.setDatesFromViewRange();
        }).catch((err) => {
          console.error('Could not load view range.')
          console.log(err);
        });
      };

      const getListPageSize = function () {
        let pref = new Preferences();
        return pref.getByName('listPageSize').then(data => {

          const listPageSize = data.data.data.attributes.data;
          ffStore.updateListPageSize(listPageSize);
        }).catch((err) => {
          console.error('Could not load listPageSize.')
          console.log(err);
        });
      };

      const getDefaultCurrency = function () {
        let curr = new Currencies();
        return curr.default().then(data => {
          let currencyId = parseInt(data.data.data.id);
          let currencyCode = data.data.data.attributes.code;
          ffStore.setCurrencyId(currencyId);
          ffStore.setCurrencyCode(currencyCode);
        }).catch((err) => {
          console.error('Could not load preferences.');
          console.log(err);

          // redirect user if 401
          if (err.response) {
            if(401 === err.response.status) {
              window.location.href = '/login';
            }
          }
        });
      };

      const getLocale = function () {
        return (new Prefs).get('locale').then(data => {
          const locale = data.data.data.attributes.data.replace('_', '-');

          ffStore.setLocale(locale);
        }).catch((err) => {
          console.error('Could not load locale.')
          console.log(err);
        });
      };


      getDefaultCurrency().then(() => {
        getViewRange();
        getListPageSize();
        getLocale();
      });
    }
  })
</script>
