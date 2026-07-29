/*
 * switch-transaction-type.js
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

function switchTransactionType(transactionType) {
    let list = document.getElementsByClassName('switch-button');
    for (let i = 0; i < list.length; i++) {
        let currentType = list[i].dataset.value;
        if (currentType === transactionType) {
            list[i].classList.add('btn-primary');
            list[i].classList.remove('btn-secondary');
        }
        if (currentType !== transactionType) {
            list[i].classList.remove('btn-primary');
            list[i].classList.add('btn-secondary');
        }
    }
    if ('withdrawal' === transactionType) {
        // hide source account name:
        document.getElementById('deposit_source_id_holder').style.display = 'none';

        // // show source account ID:
        document.getElementById('source_id_holder').style.display = 'block';

        // show destination name:
        document.getElementById('withdrawal_destination_id_holder').style.display = 'block';

        // // hide destination ID:
        document.getElementById('destination_id_holder').style.display = 'none';

        // show budget + bill
        document.getElementById('budget_id_holder').style.display = 'block';
        document.getElementById('bill_id_holder').style.display = 'block';

        // hide piggy bank:
        document.getElementById('piggy_bank_id_holder').style.display = 'none';
    }

    if (transactionType === 'deposit') {
        document.getElementById('deposit_source_id_holder').style.display = 'block';

        document.getElementById('source_id_holder').style.display = 'none';

        document.getElementById('withdrawal_destination_id_holder').style.display = 'none';

        document.getElementById('destination_id_holder').style.display = 'block';
        document.getElementById('budget_id_holder').style.display = 'none';
        document.getElementById('bill_id_holder').style.display = 'none';
        document.getElementById('piggy_bank_id_holder').style.display = 'none';
    }

    if (transactionType === 'transfer') {
        document.getElementById('deposit_source_id_holder').style.display = 'none';
        document.getElementById('source_id_holder').style.display = 'block';
        document.getElementById('withdrawal_destination_id_holder').style.display = 'none';
        document.getElementById('destination_id_holder').style.display = 'block';
        document.getElementById('budget_id_holder').style.display = 'none';
        document.getElementById('bill_id_holder').style.display = 'none';
        document.getElementById('piggy_bank_id_holder').style.display = 'block';
    }
}
export {switchTransactionType};
