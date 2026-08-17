/*
 * parse-errors.js
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

import {spliceErrorsIntoTransactions} from "./splice-errors-into-transactions.js";


export function parseErrors(data) {
    // disable all messages:
    this.notifications.error.show = true;
    this.notifications.success.show = false;
    this.notifications.wait.show = false;
    this.formStates.isSubmitting = false;
    this.notifications.error.text = i18next.t('firefly.errors_submission_v2', {errorMessage: data.message});

    if (data.hasOwnProperty('errors')) {
        this.entries = spliceErrorsIntoTransactions(data.errors, this.entries);
    }
}
