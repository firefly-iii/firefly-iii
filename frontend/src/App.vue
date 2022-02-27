<template>
  <router-view/>
</template>
<script>
import {defineComponent} from 'vue';
import Preferences from "./api/preferences";
import Currencies from "./api/currencies";
import {setDatesFromViewRange} from "./store/fireflyiii/actions";

export default defineComponent(
  {
    name: 'App',
    preFetch({store}) {

      store.dispatch('fireflyiii/refreshCacheKey');

      const getViewRange = function() {
        let pref = new Preferences();
        return pref.getByName('viewRange').then(data  => {

          const viewRange = data.data.data.attributes.data;
          store.commit('fireflyiii/updateViewRange', viewRange);
          store.dispatch('fireflyiii/setDatesFromViewRange');
        }).catch((err) => {
          console.error('Could not load view range.')
          console.log(err);
        });
      };

      const getListPageSize = function() {
        let pref = new Preferences();
        return pref.getByName('listPageSize').then(data  => {

          const listPageSize = data.data.data.attributes.data;
          store.commit('fireflyiii/updateListPageSize', listPageSize);
        }).catch((err) => {
          console.error('Could not load listPageSize.')
          console.log(err);
        });
      };

      const getDefaultCurrency = function() {
        let curr = new Currencies();
        return curr.default().then(data  => {
          let currencyId = parseInt(data.data.data.id);
          let currencyCode = data.data.data.attributes.code;
          store.commit('fireflyiii/setCurrencyId', currencyId);
          store.commit('fireflyiii/setCurrencyCode', currencyCode);
        }).catch((err) => {
          console.error('Could not load preferences.');
          console.log(err);
        });
      };


      getDefaultCurrency().then(() => {
        getViewRange();
        getListPageSize();
      });
    }
  })
</script>
