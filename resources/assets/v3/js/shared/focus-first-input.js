/*
 * focus-first-input.js
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

export default function focusFirstInput() {
    let list = document.querySelectorAll('div.app-content form input[type="search"]:enabled');
    if (list.length > 0) {
        list[0].focus();
        console.log('[a] Focus on first search.');
        return;
    }
    list = document.querySelectorAll('div.app-content input[type="search"]:enabled');
    if (list.length > 0) {
        list[0].focus();
        console.log('[b] Focus on first search.');

        return;
    }
    list = document.querySelectorAll('div.app-content form input[type="text"]:enabled');
    if (list.length > 0) {
        list[0].focus();
        console.log('[c] Focus on first text.');
        return;
    }
    list = document.querySelectorAll('div.app-content input[type="text"]:enabled');
    if (list.length > 0) {
        list[0].focus();
        console.log('[d] Focus on first text.');
    }

}
