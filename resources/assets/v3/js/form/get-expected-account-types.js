/*
 * get-expected-account-types.js
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

export default function getExpectedAccountTypes(transactionType, sourceType, destinationType) {
    transactionType = transactionType.toLowerCase();
    console.log(
        "getExpectedAccountTypes: transactionType=" +
            transactionType +
            ", sourceType=" +
            sourceType +
            ", destinationType=" +
            destinationType,
    );
    let list;
    // is withdrawal and source is asset? limit destination types
    if ("withdrawal" === transactionType && "Asset account" === sourceType) {
        list = ["Expense account", "Asset account", "Loan", "Debt", "Mortgage"];
        console.log("[a] Return ", list);
        return list;
    }
    // is withdrawal and source is liability? Limit destination types
    if ("withdrawal" === transactionType && ["Loan", "Debt", "Mortgage"].includes(sourceType)) {
        // 2026-11-09 do not limit this list yet (issue #13077)
        list = ["Expense account", "Asset account", "Loan", "Debt", "Mortgage"];
        console.log("[b] Return ", list);
        return list;
    }

    // is deposit? limit destination types.
    if ("deposit" === transactionType) {
        list = ["Asset account", "Loan", "Debt", "Mortgage"];
        console.log("[b] Return ", list);
        return list;
    }
    // is transfer or withdrawal? No limit to source types.
    // if ("deposit" !== transactionType) {
    //     return ["Expense account", "Loan", "Debt", "Mortgage", "Asset account"];
    // }
    list = ["Expense account", "Loan", "Debt", "Mortgage", "Asset account"];
    console.log("[b] Return ", list);
    return list;
}
