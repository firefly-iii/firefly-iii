<?php
/**
 * BudgetList.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\Support\Binder;

use FireflyIII\Models\Budget;
use Illuminate\Routing\Route;
use Illuminate\Support\Collection;
use Log;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class BudgetList.
 */
class BudgetList implements BinderInterface
{
    /**
     * @param string $value
     * @param Route $route
     *
     * @return Collection
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     *
     */
    public static function routeBinder(string $value, Route $route): Collection
    {
        //Log::debug(sprintf('Now in BudgetList::routeBinder("%s")', $value));
        if (auth()->check()) {
            $list = array_unique(array_map('\intval', explode(',', $value)));

            // @codeCoverageIgnoreStart
            if (0 === count($list)) {
                Log::warning('Budget list count is zero, return 404.');
                throw new NotFoundHttpException;
            }
            // @codeCoverageIgnoreEnd

            /** @var Collection $collection */
            $collection = auth()->user()->budgets()
                                ->where('active', 1)
                                ->whereIn('id', $list)
                                ->get();
            //Log::debug(sprintf('Found %d active budgets', $collection->count()), $list);

            // add empty budget if applicable.
            if (in_array(0, $list, true)) {
                //Log::debug('Add empty budget because $list contains 0.');
                $collection->push(new Budget);
            }

            if ($collection->count() > 0) {
                //Log::debug(sprintf('List length is > 0 (%d), so return it.', $collection->count()));

                return $collection;
            }
            //Log::debug('List length is zero, fall back to 404.');
        }
        Log::warning('BudgetList fallback to 404.');
        throw new NotFoundHttpException;
    }
}
