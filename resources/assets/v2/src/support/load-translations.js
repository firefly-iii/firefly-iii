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


import i18next from "i18next";
import ChainedBackend from "i18next-chained-backend";
import HttpBackend from "i18next-http-backend";
import LocalStorageBackend from "i18next-localstorage-backend";

let loaded = false;

function loadTranslations(locale) {
    if (false === loaded) {
        const replacedLocale = locale.replace('-', '_');
        loaded = true;
        const expireTime = import.meta.env.MODE === 'development' ? 1 : 7 * 24 * 60 * 60 * 1000;
        console.log('Will load language "'+replacedLocale+'"');
        return i18next
            .use(ChainedBackend)
            .init({
                load: 'languageOnly',
                fallbackLng: "en",
                lng: replacedLocale,
                debug: import.meta.env.MODE === 'development',
                backend: {
                    backends: [
                        LocalStorageBackend,
                        HttpBackend
                    ],
                    backendOptions: [{
                        load: 'languageOnly',
                        expirationTime: expireTime
                    }, {
                        loadPath: './v2/i18n/{{lng}}.json'
                    }]
                }
            });
    }
    console.warn('Loading translations skipped.');
    return Promise.resolve();
}

export {loadTranslations};
