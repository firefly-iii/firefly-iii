/*
 * edit-bulk.js
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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

/** global: what */

$(document).ready(function () {
    "use strict";
    initTagsAC();
    initCategoryAC();

    // on change, remove the checkbox.
    $('input[name="category"]').change(function () {
        $('input[name="ignore_category"]').attr('checked', false);
    });

    $('select[name="budget_id"]').change(function () {

        $('input[name="ignore_budget"]').attr('checked', false);
    });

    $('input[name="tags"]').on('itemAdded', function(event) {
        $('input[name="ignore_tags"]').attr('checked', false);

    });



});