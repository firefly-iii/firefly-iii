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

function loadTranslations(language, locale) {
    if (false === loaded) {
        console.log('Will load languages', language, locale);
        let devMode = true;
        loaded = true;
        const expireTime = devMode ? 1 : 7 * 24 * 60 * 60 * 1000;
        let unique = [...new Set([language, locale, "en-US"])];
        console.log('Set of languages', unique);
        let options = {
            supportedLngs: unique,
            load: "all",
            preload: unique,
            fallbackLng: ["en-US"],
            lng: language,
            debug: devMode,
            backend: {
                backends: [LocalStorageBackend, HttpBackend],
                backendOptions: [
                    {
                        expirationTime: expireTime,
                        loadPath: "./v3/i18n/{{lng}}.json",
                    },
                    {
                        loadPath: "./v3/i18n/{{lng}}.json",
                    },
                ],
            },
        };
        console.log(options);

        return i18next.use(ChainedBackend).init(options);
    }
    return Promise.resolve();
}

export { loadTranslations };
