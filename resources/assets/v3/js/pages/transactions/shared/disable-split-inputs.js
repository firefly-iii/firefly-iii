/*
 * disable-split-accounts.js
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

// what happens when a user adds more than one split?
import Autocomplete from "bootstrap5-autocomplete";
import getExpectedAccountTypes from "../../../form/get-expected-account-types.js";

export function disableSplitInputs() {
    let isTransferOrWithdrawal =
        "transfer" === this.groupProperties.transactionType || "withdrawal" === this.groupProperties.transactionType;
    let isTransferOrDeposit =
        "transfer" === this.groupProperties.transactionType || "deposit" === this.groupProperties.transactionType;
    //console.log('Activate disableSplitInputs');
    // disable source and/or destination, based on account type.
    for (let i = 0; i < this.entries.length; i++) {
        let transactionType = this.groupProperties.transactionType;
        let sourceType = this.entries[i].source_account.type ?? "unknown";
        let destinationType = this.entries[i].destination_account.type ?? "unknown";

        let newTypes = getExpectedAccountTypes(transactionType, sourceType, destinationType);

        const el = document.getElementById("dest_" + i);
        const inst = Autocomplete.getInstance(el);
        if (null !== inst) {
            let params = inst.getConfig("serverParams");
            params.types = newTypes;
            inst.setConfig("serverParams", params);
        }

        if (i > 0) {
            // disable dates
            this.entries[i].date_disabled = true;

            // if is withdrawal, pre-fill the destination account with the first entry's destination account.
            // unless the user changes it, in which case we will not overwrite it.
            if (
                "" === this.entries[i].destination_account.alpine_name &&
                "withdrawal" === this.groupProperties.transactionType
            ) {
                console.log("Prefill destination account #", i, this.entries[i].destination_account);
                this.entries[i].destination_account = JSON.parse(
                    JSON.stringify(this.entries[i - 1].destination_account),
                ); //structuredClone();
            }

            // disable source when withdrawal or transfer
            if (isTransferOrWithdrawal) {
                this.entries[i].source_account.disabled = true;
                // console.log('Disable source account #' + i + 1);
            }

            // disable destination when deposit or transfer
            if (isTransferOrDeposit) {
                this.entries[i].destination_account.disabled = true;
                // console.log('Disable destination account #' + i + 1);
            }
        }
    }
}
