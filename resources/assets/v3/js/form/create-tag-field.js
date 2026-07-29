/*
 * create-tag-field.js
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
import Tags from "bootstrap5-tags";
function createTagField(fieldIdentifier) {
    let token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    Tags.init('#' + fieldIdentifier,
        {
            allowNew: true,
            allowClear: true,
            server: './api/v1/autocomplete/tags?_token=' + token,
            liveServer: true,
            labelField: 'name',
            valueField: 'name',
            fetchOptions: {
                method: 'GET',
                credentials: 'include',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token
                }
            }
        }
    );
}

export {createTagField};
