/*
 * cr.js
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

import Autocomplete from "bootstrap5-autocomplete";
import formatMoney from "../../../util/format-money.js";
import { format } from "date-fns";

export function createLinkAutocomplete(fieldIdentifier, url) {
    const renderJournal = function (item) {
        let locale = window.store.get("locale");
        // return item.description;
        return (
            item.description +
            '<br><small class="text-muted">#' +
            item.transaction_group_id +
            ", " +
            formatMoney(item.amount, item.currency_code) +
            " @ " +
            format(new Date(item.date), window.i18next.t("config.date_time_fns", { lng: locale }), locale) +
            "</small>"
        );
    };
    // console.log("Created link AC", fieldIdentifier);
    Autocomplete.init("#" + fieldIdentifier, {
        server: url,
        labelField: "name",
        hiddenInput: true,
        valueField: "id",
        liveServer: true,
        // fixed: true,
        showAllSuggestions: true,
        onRenderItem: renderJournal,
        fetchOptions: {
            method: "GET",
            headers: {
                "Content-Type": "application/json",
                Accept: "application/json",
                "X-CSRF-TOKEN": document.head.querySelector('meta[name="csrf-token"]').content,
            },
        },
    });
}
