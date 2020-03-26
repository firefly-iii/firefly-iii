/*
 * create.js
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

$(function () {
    "use strict";
    $(".content-wrapper form input:enabled:visible:first").first().focus().select();

    $('#ffInput_auto_budget_type').change(updateAutoBudget);

    function updateAutoBudget() {
        var value = parseInt($('#ffInput_auto_budget_type').val());
        if (0 === value) {
            $('#ffInput_auto_budget_currency_id').prop('disabled', true);
            $('#ffInput_auto_budget_amount').prop('disabled', true);
            $('#ffInput_auto_budget_period').prop('disabled', true);
            return;
        }
        $('#ffInput_auto_budget_currency_id').prop('disabled', false);
        $('#ffInput_auto_budget_amount').prop('disabled', false);
        $('#ffInput_auto_budget_period').prop('disabled', false);
    }

    updateAutoBudget();
    
});