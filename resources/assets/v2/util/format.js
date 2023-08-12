/*
 * format.js
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

import {format} from 'date-fns'
import {
    bg,
    cs,
    da,
    de,
    el,
    enGB,
    enUS,
    es,
    ca,
    fi,
    fr,
    hu,
    id,
    it,
    ja,
    ko,
    nb,
    nn,
    nl,
    pl,
    ptBR,
    pt,
    ro,
    ru,
    sk,
    sl,
    sv,
    tr,
    uk,
    vi,
    zhTW,
    zhCN
} from 'date-fns/locale'

const locales = {
    bg,
    cs,
    da,
    de,
    el,
    enGB,
    enUS,
    es,
    ca,
    fi,
    fr,
    hu,
    id,
    it,
    ja,
    ko,
    nb,
    nn,
    nl,
    pl,
    ptBR,
    pt,
    ro,
    ru,
    sk,
    sl,
    sv,
    tr,
    uk,
    vi,
    zhTW,
    zhCN
}

// by providing a default string of 'PP' or any of its variants for `formatStr`
// it will format dates in whichever way is appropriate to the locale
export default function (date, formatStr = 'PP') {
    let locale = window.__localeId__.replace('_', '');
    return format(date, formatStr, {
        locale: locales[locale] ?? locales[locale.slice(0, 2)] ?? locales['enUS'] // or global.__localeId__
    })
}
