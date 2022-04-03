/*
 * mutations.js
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
export function someMutation (state) {
}
*/

export const updateViewRange = (state, viewRange) => {
  state.viewRange = viewRange;
}

export const updateListPageSize = (state, value) => {
  state.listPageSize = value;
}

export const setRange = (state, value) => {
  state.range = value;
}

export const setDefaultRange = (state, value) => {
  state.defaultRange = value;
}

export const setCurrencyCode = (state, value) => {
  state.currencyCode = value;
}
export const setCurrencyId = (state, value) => {
  state.currencyId = value;
}

export const setCacheKey = (state, value) => {
  state.cacheKey = value;
}
