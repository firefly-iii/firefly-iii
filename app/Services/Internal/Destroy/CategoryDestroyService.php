<?php
/**
 * CategoryDestroyService.php
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

namespace FireflyIII\Services\Internal\Destroy;

use DB;
use Exception;
use FireflyIII\Models\Category;
use Log;

/**
 * Class CategoryDestroyService
 * @codeCoverageIgnore
 */
class CategoryDestroyService
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        if ('testing' === config('app.env')) {
            Log::warning(sprintf('%s should not be instantiated in the TEST environment!', get_class($this)));
        }
    }

    /**
     * @param Category $category
     */
    public function destroy(Category $category): void
    {
        try {
            $category->delete();
        } catch (Exception $e) { // @codeCoverageIgnore
            Log::error(sprintf('Could not delete category: %s', $e->getMessage())); // @codeCoverageIgnore
        }

        // also delete all relations between categories and transaction journals:
        DB::table('category_transaction_journal')->where('category_id', (int)$category->id)->delete();

        // also delete all relations between categories and transactions:
        DB::table('category_transaction')->where('category_id', (int)$category->id)->delete();
    }
}
