<?php
/**
 * BillUpdateService.php
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

namespace FireflyIII\Services\Internal\Update;

use FireflyIII\Factory\TransactionCurrencyFactory;
use FireflyIII\Models\Bill;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Services\Internal\Support\BillServiceTrait;
use Log;

/**
 * @codeCoverageIgnore
 * Class BillUpdateService
 */
class BillUpdateService
{
    use BillServiceTrait;

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
     * @param Bill $bill
     * @param array $data
     *
     * @return Bill
     */
    public function update(Bill $bill, array $data): Bill
    {
        /** @var TransactionCurrencyFactory $factory */
        $factory = app(TransactionCurrencyFactory::class);
        /** @var TransactionCurrency $currency */
        $currency = $factory->find($data['currency_id'] ?? null, $data['currency_code'] ?? null);

        if (null === $currency) {
            // use default currency:
            $currency = app('amount')->getDefaultCurrencyByUser($bill->user);
        }

        // enable the currency if it isn't.
        $currency->enabled = true;
        $currency->save();

        $oldName                       = $bill->name;
        $bill->name                    = $data['name'];
        $bill->match                   = $data['match'] ?? $bill->match;
        $bill->amount_min              = $data['amount_min'];
        $bill->amount_max              = $data['amount_max'];
        $bill->date                    = $data['date'];
        $bill->transaction_currency_id = $currency->id;
        $bill->repeat_freq             = $data['repeat_freq'];
        $bill->skip                    = $data['skip'];
        $bill->automatch               = true;
        $bill->active                  = $data['active'] ?? true;
        $bill->save();

        // update note:
        if (isset($data['notes'])) {
            $this->updateNote($bill, (string)$data['notes']);
        }

        // update rule actions.
        $this->updateBillActions($bill, $oldName, $data['name']);

        return $bill;
    }

}
