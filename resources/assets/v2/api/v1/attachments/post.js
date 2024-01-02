/*
 * post.js
 * Copyright (c) 2024 james@firefly-iii.org
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

import {api} from "../../../boot/axios";

export default class Post {
    post(fileName, attachableType, attachableId) {
        let url = '/api/v1/attachments';
        return api.post(url, {filename: fileName, attachable_type: attachableType, attachable_id: attachableId});
    }

    upload(id, data) {
        let url = './api/v1/attachments/' + id + '/upload';
        return axios.post(url, data);
    }
}
