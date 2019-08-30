<?php
/**
 * BudgetLimitRepository.php
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

declare(strict_types=1);

namespace FireflyIII\Repositories\Budget;


use Exception;
use FireflyIII\Models\BudgetLimit;
use FireflyIII\User;
use Log;

/**
 *
 * Class BudgetLimitRepository
 */
class BudgetLimitRepository implements BudgetLimitRepositoryInterface
{
    /** @var User */
    private $user;

    /**
     * Constructor.
     */
    public function __construct()
    {
        if ('testing' === config('app.env')) {
            Log::warning(sprintf('%s should not be instantiated in the TEST environment!', get_class($this)));
            die(get_class($this));
        }
    }

    /**
     * Destroy a budget limit.
     *
     * @param BudgetLimit $budgetLimit
     */
    public function destroyBudgetLimit(BudgetLimit $budgetLimit): void
    {
        try {
            $budgetLimit->delete();
        } catch (Exception $e) {
            Log::info(sprintf('Could not delete budget limit: %s', $e->getMessage()));
        }
    }

    /**
     * @param User $user
     */
    public function setUser(User $user): void
    {
        $this->user = $user;
    }
}