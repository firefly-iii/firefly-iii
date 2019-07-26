<?php
/**
 * CostCenterDestroyService.php
 * Copyright (c) 2018 thegrumpydictator@gmail.com
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

namespace FireflyIII\Services\Internal\Destroy;

use DB;
use Exception;
use FireflyIII\Models\CostCenter;
use Log;

/**
 * Class CostCenterDestroyService
 */
class CostCenterDestroyService
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        if ('testing' === config('app.env')) {
            Log::warning(sprintf('%s should not be instantiated in the TEST environment!', \get_class($this)));
        }
    }

    /**
     * @param CostCenter $costCenter
     */
    public function destroy(CostCenter $costCenter): void
    {
        try {
            $costCenter->delete();
        } catch (Exception $e) { // @codeCoverageIgnore
            Log::error(sprintf('Could not delete cost center: %s', $e->getMessage())); // @codeCoverageIgnore
        }

        // also delete all relations between cost centers and transaction journals:
        DB::table('cost_center_transaction_journal')->where('cost_center_id', (int)$costCenter->id)->delete();

        // also delete all relations between cost centers and transactions:
        DB::table('cost_center_transaction')->where('cost_center_id', (int)$costCenter->id)->delete();
    }
}
