<?php
declare(strict_types=1);
/**
 * ApiSupport.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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

namespace FireflyIII\Support\Http\Api;

use FireflyIII\Models\Account;
use Illuminate\Support\Collection;

/**
 * Trait ApiSupport
 * @codeCoverageIgnore
 */
trait ApiSupport
{
    /**
     * Small helper function for the revenue and expense account charts.
     *
     * @param array $names
     *
     * @return array
     */
    protected function expandNames(array $names): array
    {
        $result = [];
        foreach ($names as $entry) {
            $result[$entry['name']] = 0;
        }

        return $result;
    }

    /**
     * Small helper function for the revenue and expense account charts.
     *
     * @param Collection $accounts
     *
     * @return array
     */
    protected function extractNames(Collection $accounts): array
    {
        $return = [];
        /** @var Account $account */
        foreach ($accounts as $account) {
            $return[$account->id] = $account->name;
        }

        return $return;
    }
}
