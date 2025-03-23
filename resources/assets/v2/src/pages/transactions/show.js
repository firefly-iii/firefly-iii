/*
 * show.js
 * Copyright (c) 2024 james@firefly-iii.org.
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
 * along with this program.  If not, see https://www.gnu.org/licenses/.
 */

import '../../boot/bootstrap.js';
import dates from "../shared/dates.js";
import i18next from "i18next";
import Get from "../../api/v1/model/transaction/get.js";
import {parseDownloadedSplits} from "./shared/parse-downloaded-splits.js";
import {format} from "date-fns";
import formatMoney from "../../util/format-money.js";
import {inlineJournalDescription} from "../../support/inline-edit.js";


let show = function () {
    return {
        // notifications
        notifications: {
            error: {
                show: false, text: '', url: '',
            }, success: {
                show: false, text: '', url: '',
            }, wait: {
                show: false, text: '',

            }
        },
        groupProperties: {
            id: 0,
            transactionType: '',
            transactionTypeTranslated: '',
            title: '',
            date: new Date,
        },
        dateFields: ["book_date", "due_date", "interest_date", "invoice_date", "payment_date", "process_date"],
        metaFields: ['external_id', 'internal_reference', 'sepa_batch_id', 'sepa_ct_id', 'sepa_ct_op', 'sepa_db', 'sepa_country', 'sepa_cc', 'sepa_ep', 'sepa_ci', 'external_url'],

        // parse amounts per currency
        amounts: {},

        entries: [],

        pageProperties: {},
        formatMoney(amount, currencyCode) {
            console.log('formatting', amount, currencyCode);
            if ('' === currencyCode) {
                currencyCode = 'EUR';
            }
            return formatMoney(amount, currencyCode);
        },
        format(date) {
            return format(date, i18next.t('config.date_time_fns'));
        },
        init() {
            this.notifications.wait.show = true;
            this.notifications.wait.text = i18next.t('firefly.wait_loading_data')
            const page = window.location.href.split('/');
            const groupId = parseInt(page[page.length - 1]);
            const getter = new Get();
            getter.show(groupId, {}).then((response) => {
                const data = response.data.data;
                this.groupProperties.id = parseInt(data.id);
                this.groupProperties.transactionType = data.attributes.transactions[0].type;
                this.groupProperties.transactionTypeTranslated = i18next.t('firefly.' + data.attributes.transactions[0].type);
                this.groupProperties.title = data.attributes.title ?? data.attributes.transactions[0].description;
                this.entries = parseDownloadedSplits(data.attributes.transactions, parseInt(data.id));
                // remove waiting thing.
                this.notifications.wait.show = false;
            }).then(() => {
                for (let i in this.entries) {
                    if (this.entries.hasOwnProperty(i)) {
                        const currencyCode = this.entries[i].currency_code;
                        const foreignCurrencyCode = this.entries[i].foreign_currency_code;

                        if (undefined === this.amounts[currencyCode]) {
                            this.amounts[currencyCode] = 0;
                            this.amounts[currencyCode] += parseFloat(this.entries[i].amount);
                        }
                        if (null !== foreignCurrencyCode && '' !== foreignCurrencyCode && undefined === this.amounts[foreignCurrencyCode]) {
                            this.amounts[foreignCurrencyCode] = 0;
                            this.amounts[foreignCurrencyCode] += parseFloat(this.entries[i].foreign_amount);
                        }
                        if (0 === parseInt(i)) {
                            this.groupProperties.date = this.entries[i].date;
                        }
                    }
                }

                // at this point do the inline change fields
                const descriptions = document.querySelectorAll('.journal_description');
                for (const i in descriptions) {
                    if (descriptions.hasOwnProperty(i)) {
                        const current = descriptions[i];
                        // this is all manual work for now, and should be better
                        // TODO make better
                        current.addEventListener('save', function (e) {
                            const journalId = parseInt(e.currentTarget.dataset.id);
                            const groupId = parseInt(e.currentTarget.dataset.group);
                            const length = parseInt(e.currentTarget.dataset.length); // TODO not happy with this.
                            const newDescription = e.currentTarget.textContent;
                            console.log(length);
                            if (1 === length) {
                                // update "group" transaction title because it's equal to this journal's description.
                                document.querySelector('.group_title[data-group="' + groupId + '"]').textContent = newDescription;
                                document.querySelector('.group_title_title[data-group="' + groupId + '"]').textContent = newDescription;
                            }
                        })
                        inlineJournalDescription(current);
                    }
                }

            }).catch((error) => {
                // todo auto generated.
                this.notifications.error.show = true;
                this.notifications.error.text = error.message;
            });
        }
    }
}

let comps = {show, dates};

function loadPage() {
    Object.keys(comps).forEach(comp => {
        console.log(`Loading page component "${comp}"`);
        let data = comps[comp]();
        Alpine.data(comp, () => data);
    });
    Alpine.start();
}

// wait for load until bootstrapped event is received.
document.addEventListener('firefly-iii-bootstrapped', () => {
    console.log('Loaded through event listener.');
    loadPage();
});
// or is bootstrapped before event is triggered.
if (window.bootstrapped) {
    console.log('Loaded through window variable.');
    loadPage();
}
