<?php
/**
 * AccountUpdateService.php
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
use FireflyIII\Models\Account;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Services\Internal\Support\AccountServiceTrait;
use Log;

/**
 * Class AccountUpdateService
 */
class AccountUpdateService
{
    use AccountServiceTrait;

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
     * Update account data.
     *
     * @param Account $account
     * @param array   $data
     *
     * @return Account
     * @throws \FireflyIII\Exceptions\FireflyException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function update(Account $account, array $data): Account
    {
        // update the account itself:
        $account->name            = $data['name'];
        $account->active          = $data['active'];
        $account->virtual_balance = '' === trim($data['virtualBalance']) ? '0' : $data['virtualBalance'];
        $account->iban            = $data['iban'];
        $account->save();

        if (isset($data['currency_id']) && 0 === $data['currency_id']) {
            unset($data['currency_id']);
        }
        // find currency, or use default currency instead.
        /** @var TransactionCurrencyFactory $factory */
        $factory = app(TransactionCurrencyFactory::class);
        /** @var TransactionCurrency $currency */
        $currency = $factory->find($data['currency_id'] ?? null, $data['currency_code'] ?? null);

        if (null === $currency) {
            // use default currency:
            $currency = app('amount')->getDefaultCurrencyByUser($account->user);
        }
        $currency->enabled = true;
        $currency->save();
        $data['currency_id'] = $currency->id;

        // update all meta data:
        $this->updateMetaData($account, $data);

        // has valid initial balance (IB) data?
        if ($this->validIBData($data)) {
            // then do update!
            $this->updateIB($account, $data);
        }

        // if not, delete it when exist.
        if (!$this->validIBData($data)) {
            $this->deleteIB($account);
        }

        // update note:
        if (isset($data['notes'])) {
            $this->updateNote($account, (string)$data['notes']);
        }

        return $account;
    }
}
