/*
 * autostep.js
 * Copyright (c) 2026 james@firefly-iii.org
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

// this method automatically calls some
// "next steps", whenever the state of the form loads or changes.
// used during the init phase of the form.
import {loadLinkTypes} from "./load-link-types.js";

export function autoStep() {
    // check if custom field "links" is enabled and if so, load the link types and save them in formData.
    if (null === this.formStates.loadingLinks && this.formBehaviour.customFields.hasOwnProperty('links') && true === this.formBehaviour.customFields.links) {
        this.formStates.loadingLinks = true;
        loadLinkTypes().then(data => {
            console.log('done loadLinkTypes()');
            for (let i = 0; i < data.length; i++) {
                if (data.hasOwnProperty(i)) {
                    let current = data[i];
                    current.id = parseInt(current.id);
                    this.formData.linkTypes.push(current);
                }
            }
            this.formStates.loadingLinks = false;
            this.autoStep(); // yes, recurring.

        });
    }
    if (this.formBehaviour.customFields.hasOwnProperty('links') && false === this.formBehaviour.customFields.links) {
        this.formStates.storedLinks = true;
        this.formStates.loadingLinks = false;
    }
    // check if the transaction is loaded and also the transaction links AND the field is enabled, then load the autocomplete for links.
    if (false === this.formStates.loadingTransaction && false === this.formStates.loadingLinks && this.formBehaviour.customFields.hasOwnProperty('links') && true === this.formBehaviour.customFields.links) {
        for(let i = 0; i < this.entries.length; i++) {
            if(this.entries.hasOwnProperty(i)) {
                this.createLinkAutocomplete('links_modal_search_' + i, 'api/v1/autocomplete/transactions-with-meta');
                if(0 !== parseInt(this.entries[i].transaction_journal_id)) {
                    // load the links for this transaction journal.
                    this.loadTransactionLinks(i, parseInt(this.entries[i].transaction_journal_id));
                }
            }
        }
    }

}
