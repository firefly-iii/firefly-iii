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
export function disableSplitAccounts() {
    //console.log('Call disableSplitAccounts');
    if(this.entries.length > 1) {
        //console.log('Activate disableSplitAccounts');
        // disable source and/or destination, based on account type.
        for(let i = 1;i<this.entries.length;i++) {
            // disable source when withdrawal or transfer
            if('transfer' === this.groupProperties.transactionType || 'withdrawal' === this.groupProperties.transactionType) {
                this.entries[i].source_account.disabled = true;
                // console.log('Disable source account #' + i + 1);
            }
            // disable destination when deposit or transfer
            if('transfer' === this.groupProperties.transactionType || 'deposit' === this.groupProperties.transactionType) {
                this.entries[i].destination_account.disabled = true;
                // console.log('Disable destination account #' + i + 1);
            }
        }
    }
}
