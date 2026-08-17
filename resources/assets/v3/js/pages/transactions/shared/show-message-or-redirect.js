/*
 * show-message-or-redirect.js
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

import i18next from "i18next";

export function showMessageOrRedirectUser() {
    // disable all messages:
    this.notifications.error.show = false;
    this.notifications.success.show = false;
    this.notifications.wait.show = false;

    if (this.formStates.returnHereButton) {
        this.formStates.isSubmitting = false;
        this.notifications.success.show = true;
        this.notifications.success.url = 'transactions/show/' + parseInt(this.groupProperties.id);

        // title depends on form role.
        if('create' === this.formBehaviour.formType) {
            this.notifications.success.text = i18next.t('firefly.stored_journal_js', {description: this.groupProperties.title, interpolation: {escapeValue: false}});
            this.groupProperties.title = null;

            // reset the form.
            if (this.formStates.resetButton) {
                this.entries = [];
                this.addSplit();
                this.groupProperties.totalAmount = 0;
            }
        }
        if('edit' === this.formBehaviour.formType) {
            let title = this.groupProperties.title;
            if('' === title) {
                title = this.entries[0].description;
            }
            this.notifications.success.text = i18next.t('firefly.updated_journal_js', {description: title});
        }


        return;
    }
    // the redirect also depends on the "from" in the query param, which is validated by Firefly III on the server side.
    // get from parameter from query
    const urlParams = new URLSearchParams(window.location.search);
    const from = urlParams.get('_from').toString();

    // grab base href
    let baseHref = document.querySelector('base').getAttribute('href');
    baseHref = baseHref.substring(0, baseHref.length - 1);

    if ('' !== from) {
        console.log('Redirect to valid _from parameter: ' + baseHref + ' ' +  from);
        window.location = baseHref + from;
        return;
    }
    if('edit' === this.formBehaviour.formType) {
        window.location = 'transactions/show/' + this.groupProperties.id + '?transaction_group_id=' + this.groupProperties.id + '&message=updated';
        return;
    }
    window.location = 'transactions/show/' + this.groupProperties.id + '?transaction_group_id=' + this.groupProperties.id + '&message=created';
}
