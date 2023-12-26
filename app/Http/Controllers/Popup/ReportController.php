<?php

/**
 * ReportController.php
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
declare(strict_types=1);

namespace FireflyIII\Http\Controllers\Popup;

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Support\Http\Controllers\RenderPartialViews;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Class ReportController.
 */
class ReportController extends Controller
{
    use RenderPartialViews;

    /**
     * Generate popup view.
     *
     * @throws FireflyException
     */
    public function general(Request $request): JsonResponse
    {
        $attributes = $request->get('attributes') ?? [];
        $attributes = $this->parseAttributes($attributes);

        app('view')->share('start', $attributes['startDate']);
        app('view')->share('end', $attributes['endDate']);

        $html = match ($attributes['location']) {
            default               => sprintf('Firefly III cannot handle "%s"-popups.', $attributes['location']),
            'budget-spent-amount' => $this->budgetSpentAmount($attributes),
            'expense-entry'       => $this->expenseEntry($attributes),
            'income-entry'        => $this->incomeEntry($attributes),
            'category-entry'      => $this->categoryEntry($attributes),
            'budget-entry'        => $this->budgetEntry($attributes),
        };

        return response()->json(['html' => $html]);
    }
}
