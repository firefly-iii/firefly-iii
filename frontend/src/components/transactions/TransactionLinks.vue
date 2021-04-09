<!--
  - TransactionLinks.vue
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
  <div v-if="showField">
    <div class="form-group">
      <div class="text-xs d-none d-lg-block d-xl-block">
        {{ $t('firefly.journal_links') }}
      </div>
      <div class="row">
        <div class="col">
          <p v-if="links.length === 0">
            <button type="button" class="btn btn-default btn-xs" data-target="#linkModal" @click="resetModal" data-toggle="modal"><i class="fas fa-plus"></i> Add transaction link</button>
          </p>
          <ul v-if="links.length > 0" class="list-group">
            <li v-for="(transaction, index) in links" class="list-group-item" v-bind:key="index">
              <em>{{ getTextForLinkType(transaction.link_type_id) }}</em>
              <a :href='"./transaction/show/" + transaction.transaction_group_id'>{{ transaction.description }}</a>

              <span v-if="transaction.type === 'withdrawal'">
                          (<span class="text-danger">{{
                  Intl.NumberFormat(locale, {
                    style: 'currency',
                    currency: transaction.currency_code
                  }).format(parseFloat(transaction.amount) * -1)
                }}</span>)
                        </span>
              <span v-if="transaction.type === 'deposit'">
                          (<span class="text-success">{{
                  Intl.NumberFormat(locale, {
                    style: 'currency',
                    currency: transaction.currency_code
                  }).format(parseFloat(transaction.amount))
                }}</span>)
                        </span>
              <span v-if="transaction.type === 'transfer'">
                          (<span class="text-info">{{
                  Intl.NumberFormat(locale, {
                    style: 'currency',
                    currency: transaction.currency_code
                  }).format(parseFloat(transaction.amount))
                }}</span>)
                        </span>
              <div class="btn-group btn-group-xs float-right">
                <button type="button" class="btn btn-xs btn-danger" @click="removeLink(index)" tabindex="-1"><i class="far fa-trash-alt"></i></button>
              </div>
            </li>
          </ul>
          <div v-if="links.length > 0" class="form-text">
            <button type="button" class="btn btn-default" @click="resetModal" data-target="#linkModal" data-toggle="modal"><i class="fas fa-plus"></i></button>
          </div>
        </div>
      </div>
    </div>
    <!-- modal -->
    <div id="linkModal" class="modal" tabindex="-1" ref="linkModal">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Transaction thing dialog.</h5>
            <button aria-label="Close" class="close" data-dismiss="modal" type="button">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <div class="container-fluid">
              <div class="row">
                <div class="col">
                  <p>
                    Use this form to search for transactions you wish to link to this one. When in doubt, use <code>id:*</code> where the ID is the number from
                    the URL.
                  </p>
                </div>
              </div>
              <div class="row">
                <div class="col">
                  <form v-on:submit.prevent="search">
                    <div class="input-group">
                      <input id="query" v-model="query" autocomplete="off" class="form-control" maxlength="255" name="search"
                             placeholder="Search query" type="text">
                      <div class="input-group-append">
                        <button class="btn btn-default" type="submit"><i class="fas fa-search"></i> Search</button>
                      </div>
                    </div>
                  </form>
                </div>
              </div>
              <div class="row">
                <div class="col">
                  <span v-if="searching"><i class="fas fa-spinner fa-spin"></i></span>
                  <h4 v-if="searchResults.length > 0">{{ $t('firefly.search_results') }}</h4>
                  <table v-if="searchResults.length > 0" class="table table-sm">
                    <caption style="display:none;">{{ $t('firefly.search_results') }}</caption>
                    <thead>
                    <tr>
                      <th scope="col" colspan="2" style="width:33%">{{ $t('firefly.include') }}</th>
                      <th scope="col">{{ $t('firefly.transaction') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr v-for="result in searchResults">
                      <td>
                        <input v-model="result.selected" class="form-control"
                               type="checkbox"
                               @change="selectTransaction($event)"
                        />
                      </td>
                      <td>
                        <select
                            v-model="result.link_type_id"
                            class="form-control"
                            @change="selectLinkType($event)"
                        >
                          <option v-for="linkType in linkTypes" :label="linkType.type" :value="linkType.id + '-' + linkType.direction">{{
                              linkType.type
                            }}
                          </option>
                        </select>
                      </td>
                      <td>
                        <a :href="'./transactions/show/' + result.transaction_group_id">{{ result.description }}</a>
                        <span v-if="result.type === 'withdrawal'">
                          (<span class="text-danger">{{
                            Intl.NumberFormat(locale, {
                              style: 'currency',
                              currency: result.currency_code
                            }).format(parseFloat(result.amount) * -1)
                          }}</span>)
                        </span>
                        <span v-if="result.type === 'deposit'">
                          (<span class="text-success">{{
                            Intl.NumberFormat(locale, {
                              style: 'currency',
                              currency: result.currency_code
                            }).format(parseFloat(result.amount))
                          }}</span>)
                        </span>
                        <span v-if="result.type === 'transfer'">
                          (<span class="text-info">{{
                            Intl.NumberFormat(locale, {
                              style: 'currency',
                              currency: result.currency_code
                            }).format(parseFloat(result.amount))
                          }}</span>)
                        </span>
                        <br/>
                        <em>
                          <a :href="'./accounts/show/' + result.source_id">{{ result.source_name }}</a>
                          &rarr;
                          <a :href="'./accounts/show/' + result.destination_id">{{ result.destination_name }}</a>
                        </em>
                      </td>
                    </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button class="btn btn-secondary" data-dismiss="modal" type="button">Close</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
const lodashClonedeep = require('lodash.clonedeep');
// TODO error handling
export default {
  props: ['index', 'value', 'errors', 'customFields'],
  name: "TransactionLinks",
  data() {
    return {
      searchResults: [],
      include: [],
      locale: 'en-US',
      linkTypes: [],
      query: '',
      searching: false,
      links: this.value,
      availableFields: this.customFields,
      emitEvent: true
    }
  },
  created() {
    this.locale = localStorage.locale ?? 'en-US';
    this.emitEvent = false;
    this.links = lodashClonedeep(this.value);
    this.getLinkTypes();

  },
  computed: {
    showField: function () {
      if ('links' in this.availableFields) {
        return this.availableFields.links;
      }
      return false;
    }
  },
  watch: {
    value: function (value) {
      if (null !== value) {
        this.emitEvent = false;
        this.links = lodashClonedeep(value);
      }
    },
    links: function (value) {
      if (true === this.emitEvent) {
        this.$emit('set-field', {index: this.index, field: 'links', value: lodashClonedeep(value)});
      }
      this.emitEvent = true;
    },
    customFields: function (value) {
      this.availableFields = value;
    }
  },
  methods: {
    removeLink: function (index) {
      this.links.splice(index, 1);
    },
    getTextForLinkType: function (linkTypeId) {
      let parts = linkTypeId.split('-');
      for (let i in this.linkTypes) {
        if (this.linkTypes.hasOwnProperty(i) && /^0$|^[1-9]\d*$/.test(i) && i <= 4294967294) {
          let current = this.linkTypes[i];
          if (parts[0] === current.id && parts[1] === current.direction) {
            return current.type;
          }
        }
      }
      return 'text for #' + linkTypeId;
    },
    selectTransaction: function (event) {
      for (let i in this.searchResults) {
        if (this.searchResults.hasOwnProperty(i) && /^0$|^[1-9]\d*$/.test(i) && i <= 4294967294) {
          let current = this.searchResults[i];
          if (current.selected) {
            this.addToSelected(current);
          }
          if (!current.selected) {
            // remove from
            this.removeFromSelected(current);
          }
        }
      }
    },
    selectLinkType: function (event) {
      for (let i in this.searchResults) {
        if (this.searchResults.hasOwnProperty(i) && /^0$|^[1-9]\d*$/.test(i) && i <= 4294967294) {
          let current = this.searchResults[i];
          this.updateSelected(current.transaction_journal_id, current.link_type_id);
        }
      }
    },
    updateSelected(journalId, linkTypeId) {
      for (let i in this.links) {
        if (this.links.hasOwnProperty(i) && /^0$|^[1-9]\d*$/.test(i) && i <= 4294967294) {
          let current = this.links[i];
          if (parseInt(current.transaction_journal_id) === journalId) {
            this.links[i].link_type_id = linkTypeId;
          }
        }
      }
    },
    addToSelected(journal) {
      let result = this.links.find(({transaction_journal_id}) => transaction_journal_id === journal.transaction_journal_id);
      if (typeof result === 'undefined') {
        this.links.push(journal);
      }
    },
    removeFromSelected(journal) {
      for (let i in this.links) {
        if (this.links.hasOwnProperty(i) && /^0$|^[1-9]\d*$/.test(i) && i <= 4294967294) {
          let current = this.links[i];
          if (current.transaction_journal_id === journal.transaction_journal_id) {
            this.links.splice(parseInt(i), 1);
          }
        }
      }
    },
    getLinkTypes: function () {
      let url = './api/v1/link_types';
      axios.get(url)
          .then(response => {
                  this.parseLinkTypes(response.data);
                }
          );
    },
    resetModal: function() {
      this.search();
    },
    parseLinkTypes: function (data) {
      for (let i in data.data) {
        if (data.data.hasOwnProperty(i) && /^0$|^[1-9]\d*$/.test(i) && i <= 4294967294) {
          let current = data.data[i];
          let linkTypeInward = {
            id: current.id,
            type: current.attributes.inward,
            direction: 'inward'
          };
          let linkTypeOutward = {
            id: current.id,
            type: current.attributes.outward,
            direction: 'outward'
          };
          if (linkTypeInward.type === linkTypeOutward.type) {
            linkTypeInward.type = linkTypeInward.type + ' (←)';
            linkTypeOutward.type = linkTypeOutward.type + ' (→)';
          }
          this.linkTypes.push(linkTypeInward);
          this.linkTypes.push(linkTypeOutward);
        }
      }
    },
    search: function () {
      if('' === this.query) {
        this.searchResults = [];
        return;
      }
      this.searching = true;
      this.searchResults = [];
      let url = './api/v1/search/transactions?limit=10&query=' + this.query;
      axios.get(url)
          .then(response => {
                  this.parseSearch(response.data);
                }
          );
    },
    parseSearch: function (data) {
      for (let i in data.data) {
        if (data.data.hasOwnProperty(i) && /^0$|^[1-9]\d*$/.test(i) && i <= 4294967294) {
          for (let ii in data.data[i].attributes.transactions) {
            if (data.data[i].attributes.transactions.hasOwnProperty(ii) && /^0$|^[1-9]\d*$/.test(ii) && ii <= 4294967294) {
              let current = data.data[i].attributes.transactions[ii];
              current.transaction_group_id = parseInt(data.data[i].id);
              current.selected = this.isJournalSelected(current.transaction_journal_id);
              current.link_type_id = this.getJournalLinkType(current.transaction_journal_id);
              current.link_type_text = '';
              this.searchResults.push(current);
            }
          }
        }
      }
      this.searching = false;
    },
    getJournalLinkType: function (journalId) {
      for (let i in this.links) {
        if (this.links.hasOwnProperty(i) && /^0$|^[1-9]\d*$/.test(i) && i <= 4294967294) {
          let current = this.links[i];
          if (current.transaction_journal_id === journalId) {
            return current.link_type_id;
          }
        }
      }
      return '1-inward';
    },
    isJournalSelected: function (journalId) {
      for (let i in this.links) {
        if (this.links.hasOwnProperty(i) && /^0$|^[1-9]\d*$/.test(i) && i <= 4294967294) {
          let current = this.links[i];
          if (current.transaction_journal_id === journalId) {
            return true;
          }
        }
      }
      return false;
    }
  }
}
</script>
