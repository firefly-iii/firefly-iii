/*
 * getters.js
 * Copyright (c) 2022 james@firefly-iii.org
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

/*
export function someGetter (state) {
}
*/

export function getViewRange(state) {
  return state.viewRange;
}

export function getListPageSize(state) {
  return state.listPageSize;
}

export function getCurrencyCode(state) {
  return state.currencyCode;
}
export function getCurrencyId(state) {
  return state.currencyId;
}

export function getRange(state) {
  return state.range;
}
export function getDefaultRange(state) {
  return state.defaultRange;
}

export function getCacheKey(state) {
  return state.cacheKey;
}
