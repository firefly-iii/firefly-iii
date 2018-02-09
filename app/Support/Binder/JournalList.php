<?php
/**
 * JournalList.php
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

use Illuminate\Routing\Route;
use Illuminate\Support\Collection;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class JournalList.
 */
class JournalList implements BinderInterface
{
    /**
     * @param string $value
     * @param Route  $route
     *
     * @return mixed
     */
    public static function routeBinder(string $value, Route $route):  Collection
    {
        if (auth()->check()) {
            $list     = [];
            $incoming = explode(',', $value);
            foreach ($incoming as $entry) {
                $list[] = intval($entry);
            }
            $list = array_unique($list);
            if (count($list) === 0) {
                throw new NotFoundHttpException; // @codeCoverageIgnore
            }

            /** @var \Illuminate\Support\Collection $collection */
            $collection = auth()->user()->transactionJournals()
                                ->whereIn('transaction_journals.id', $list)
                                ->where('transaction_journals.completed', 1)
                                ->get(['transaction_journals.*']);

            if ($collection->count() > 0) {
                return $collection;
            }
        }
        throw new NotFoundHttpException;
    }
}
