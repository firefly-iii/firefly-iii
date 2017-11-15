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
 * along with Firefly III.  If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\Support\Binder;

use FireflyIII\Models\Budget;
use Illuminate\Support\Collection;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class BudgetList.
 */
class BudgetList implements BinderInterface
{
    /**
     * @param $value
     * @param $route
     *
     * @return mixed
     */
    public static function routeBinder($value, $route): Collection
    {
        if (auth()->check()) {
            $ids = explode(',', $value);
            /** @var \Illuminate\Support\Collection $object */
            $object = Budget::where('active', 1)
                            ->whereIn('id', $ids)
                            ->where('user_id', auth()->user()->id)
                            ->get();

            // add empty budget if applicable.
            if (in_array('0', $ids)) {
                $object->push(new Budget);
            }

            if ($object->count() > 0) {
                return $object;
            }
        }
        throw new NotFoundHttpException;
    }
}
