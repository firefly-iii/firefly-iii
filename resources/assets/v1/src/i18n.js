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
        'af': require('./locales/af.json'),
        'bg': require('./locales/bg.json'),
        'ca-es': require('./locales/ca.json'),
        'cs': require('./locales/cs.json'),
        'da': require('./locales/da.json'),
        'de': require('./locales/de.json'),
        'el': require('./locales/el.json'),
        'en': require('./locales/en.json'),
        'en-us': require('./locales/en.json'),
        'en-gb': require('./locales/en-gb.json'),
        'es': require('./locales/es.json'),
        'fi': require('./locales/fi.json'),
        'fr': require('./locales/fr.json'),
        'hu': require('./locales/hu.json'),
        'id': require('./locales/id.json'),
        'it': require('./locales/it.json'),
        'ja': require('./locales/ja.json'),
        'ko': require('./locales/ko.json'),
        'nb': require('./locales/nb.json'),
        'nl': require('./locales/nl.json'),
        'nn': require('./locales/nn.json'),
        'pl': require('./locales/pl.json'),
        'pt-br': require('./locales/pt-br.json'),
        'pt-pt': require('./locales/pt.json'),
        'pt': require('./locales/pt.json'),
        'ro': require('./locales/ro.json'),
        'ru': require('./locales/ru.json'),
        'sk': require('./locales/sk.json'),
        'sl': require('./locales/sl.json'),
        'sr': require('./locales/sl.json'),
        'sv': require('./locales/sv.json'),
        'tr': require('./locales/tr.json'),
        'uk': require('./locales/uk.json'),
        'vi': require('./locales/vi.json'),
        'zh': require('./locales/zh-cn.json'),
        'zh-tw': require('./locales/zh-tw.json'),
        'zh-cn': require('./locales/zh-cn.json'),
    }
});
