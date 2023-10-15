/*
 * load-translations.js
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

let loaded = false;

async function loadTranslations(i18n, locale) {
    if (false === loaded) {
        locale = locale.replace('-', '_');
        const response = await fetch(`./v2/i18n/${locale}.json`);
        const translations = await response.json();
        i18n.store(translations);
    }
    //loaded = true;
}

export {loadTranslations};
