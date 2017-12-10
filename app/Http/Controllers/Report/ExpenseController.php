<?php
/**
 * ExpenseController.php
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

namespace FireflyIII\Http\Controllers\Report;

use Carbon\Carbon;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use Illuminate\Support\Collection;


/**
 * Class ExpenseController
 */
class ExpenseController extends Controller
{
    /** @var AccountRepositoryInterface */
    protected $accountRepository;

    /**
     *
     */
    public function __construct()
    {
        parent::__construct();

        // translations:
        $this->middleware(
            function ($request, $next) {
                $this->accountRepository = app(AccountRepositoryInterface::class);

                return $next($request);
            }
        );
    }

    /**
     * @param Collection $accounts
     * @param Collection $expense
     * @param Carbon     $start
     * @param Carbon     $end
     */
    public function spentGrouped(Collection $accounts, Collection $expense, Carbon $start, Carbon $end)
    {
        $combined = $this->combineAccounts($expense);
        // for period, get spent and earned for each account (by name)
        /**
         * @var string $name
         * @var Collection $combi
         */
        foreach($combined as $name => $combi) {

        }
    }

    protected function combineAccounts(Collection $accounts): array
    {
        $combined = [];
        /** @var Account $expenseAccount */
        foreach ($accounts as $expenseAccount) {
            $combined[$expenseAccount->name] = [$expenseAccount];
            $revenue = $this->accountRepository->findByName($expenseAccount->name, [AccountType::REVENUE]);
            if (!is_null($revenue->id)) {
                $combined[$expenseAccount->name][] = $revenue;
            }
        }

        return $combined;
    }

}