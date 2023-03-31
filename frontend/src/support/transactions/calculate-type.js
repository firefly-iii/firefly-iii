/*
 * calculate-type.js
 * Copyright (c) 2023 james@firefly-iii.org
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

export default class CalculateType {
  calculateType(source, destination) {
    const srcEmpty = this.empty(source);
    const dstEmpty = this.empty(destination);
    // both are null or ''
    if (srcEmpty && dstEmpty) {
      return 'unknown';
    }

    // source has data, dest has not
    if (typeof source === 'object' && null !== source && dstEmpty) {
      if (source.type === 'Asset account' || source.type === 'Loan' || source.type === 'Debt' || source.type === 'Mortgage') {
        return 'withdrawal';
      }
      if (source.type === 'Revenue account') {
        return 'deposit';
      }
    }
    // dst has data, source has not
    if (typeof destination === 'object' && null !== destination && srcEmpty) {
      if (destination.type === 'Asset account') {
        return 'deposit';
      }
    }
    // both have data:
    if (!srcEmpty && !dstEmpty) {
      if (source.type === 'Asset account' && destination.type === 'Expense account') {
        return 'withdrawal';
      }
      if (source.type === destination.type) {
        return 'transfer';
      }
    }

    console.error('Cannot handle');
    console.log(source);
    console.log(destination);
  }

  empty(value) {
    if (null === value || '' === value) {
      return true;
    }
    if (null !== value && typeof value === 'object') {
      return false;
    }
    return true;
  }
}
