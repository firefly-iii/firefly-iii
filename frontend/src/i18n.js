/*
 * i18n.js
 * Copyright (c) 2020 james@firefly-iii.org
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

// Create VueI18n instance with options
module.exports = new vuei18n({
    locale: document.documentElement.lang, // set locale
    fallbackLocale: 'en',
    messages: {
        'bg': require('./locales/bg.json'),
        'cs': require('./locales/cs.json'),
        'de': require('./locales/de.json'),
        'en': require('./locales/en.json'),
        'es': require('./locales/es.json'),
        'el': require('./locales/el.json'),
        'fr': require('./locales/fr.json'),
        'hu': require('./locales/hu.json'),
        'id': require('./locales/id.json'),
        'it': require('./locales/it.json'),
        'nl': require('./locales/nl.json'),
        'nb': require('./locales/nb.json'),
        'pl': require('./locales/pl.json'),
        'fi': require('./locales/fi.json'),
        'pt-br': require('./locales/pt-br.json'),
        'ro': require('./locales/ro.json'),
        'ru': require('./locales/ru.json'),
        'zh-tw': require('./locales/zh-tw.json'),
        'zh-cn': require('./locales/zh-cn.json'),
        'sv': require('./locales/sv.json'),
        'vi': require('./locales/vi.json'),
    }
});
