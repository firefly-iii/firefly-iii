/*
 * cancel-confirm.js
 * Copyright (c) 2019 james@firefly-iii.org
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

// Shared "your changes will be discarded" confirmation for the budget
// create/edit forms (see partials.budget-cancel-modal). Intercepts the
// Cancel button and any sidebar navigation link so leaving the page always
// confirms first; "Understood" then continues on to whichever destination
// triggered the modal.
$(function () {
    "use strict";

    var $modal = $('#cancelCreateBudgetModal');
    if (0 === $modal.length) {
        return;
    }

    // "Understood"'s href is the Cancel button's own destination by default;
    // captured once so it can always be restored, even after a sidebar click
    // has temporarily pointed it somewhere else.
    var $understood         = $('#cancelCreateBudgetUnderstood');
    var defaultDestination  = $understood.attr('href');

    $('#cancelCreateBudgetBtn').on('click', function () {
        $understood.attr('href', defaultDestination);
        $modal.modal('show');
    });

    // Only real navigation is intercepted: treeview parents (href="#", just
    // expand/collapse a submenu) are left alone so AdminLTE's own toggle
    // behaviour still works.
    $('.sidebar-menu a').on('click', function (e) {
        var href = $(this).attr('href');
        if (!href || '#' === href) {
            return;
        }
        e.preventDefault();
        $understood.attr('href', href);
        $modal.modal('show');
    });
});
