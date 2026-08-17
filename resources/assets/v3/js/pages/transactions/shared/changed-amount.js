/*
 * changed-amount.js
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

export function changedAmount(e) {
    const index = parseInt(e.target.dataset.index);
    this.entries[index].amount = parseFloat(e.target.value);
    this.groupProperties.totalAmount = 0;
    for (let i in this.entries) {
        if (this.entries.hasOwnProperty(i)) {
            this.groupProperties.totalAmount = this.groupProperties.totalAmount + parseFloat(this.entries[i].amount);
        }
    }
}
