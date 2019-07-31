<?php
/**
 * GetBudgetsRequest.php
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

namespace FireflyIII\Services\Ynab\Request;

use Log;

/**
 * Class GetBudgetsRequest
 * @codeCoverageIgnore
 */
class GetBudgetsRequest extends YnabRequest
{
    /** @var array */
    public $budgets;

    public function __construct()
    {
        parent::__construct();
        $this->budgets = [];
    }

    /**
     *
     */
    public function call(): void
    {
        Log::debug('Now in GetBudgetsRequest::call()');
        $uri = $this->api . '/budgets';

        Log::debug(sprintf('URI is %s', $uri));

        $result = $this->authenticatedGetRequest($uri, []);
        Log::debug('Raw GetBudgetsRequest result', $result);

        // expect data in [data][budgets]
        $rawBudgets   = $result['data']['budgets'] ?? [];
        $freshBudgets = [];
        foreach ($rawBudgets as $rawBudget) {
            Log::debug(sprintf('Raw content of budget is: %s', json_encode($rawBudget)));
            Log::debug(sprintf('Content of currency format is: %s', json_encode($rawBudget['currency_format'] ?? [])));
            Log::debug(sprintf('ISO code is: %s', $rawBudget['currency_format']['iso_code'] ?? '(none)'));
            $freshBudgets[] = [
                'id'            => $rawBudget['id'],
                'name'          => $rawBudget['name'],
                'currency_code' => $rawBudget['currency_format']['iso_code'] ?? null,
            ];
        }
        $this->budgets = $freshBudgets;
    }
}
