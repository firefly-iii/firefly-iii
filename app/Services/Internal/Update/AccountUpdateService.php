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

use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Services\Internal\Support\AccountServiceTrait;
use FireflyIII\User;
use Log;

/**
 * Class AccountUpdateService
 */
class AccountUpdateService
{
    use AccountServiceTrait;

    /** @var AccountRepositoryInterface */
    protected $accountRepository;
    /** @var array */
    private $canHaveVirtual;
    /** @var User */
    private $user;

    /**
     * Constructor.
     */
    public function __construct()
    {
        if ('testing' === config('app.env')) {
            Log::warning(sprintf('%s should not be instantiated in the TEST environment!', get_class($this)));
        }
        // TODO move to configuration.
        $this->canHaveVirtual    = [AccountType::ASSET, AccountType::DEBT, AccountType::LOAN, AccountType::MORTGAGE, AccountType::CREDITCARD];
        $this->accountRepository = app(AccountRepositoryInterface::class);
    }

    /**
     * Update account data.
     *
     * @param Account $account
     * @param array   $data
     *
     * @return Account
     */
    public function update(Account $account, array $data): Account
    {
        $this->accountRepository->setUser($account->user);
        $this->user = $account->user;

        // update the account itself:

        $account->name   = $data['name'] ?? $account->name;
        $account->active = $data['active'] ?? $account->active;
        if (null !== $data['virtual_balance']) {
            $account->virtual_balance = '' === trim($data['virtual_balance']) ? '0' : $data['virtual_balance'];
        }
        $account->iban = $data['iban'] ?? $account->iban;
        $account->save();


        if (isset($data['currency_id']) && null !== $data['currency_id'] && 0 === $data['currency_id']) {
            unset($data['currency_id']);
        }


        // find currency, or use default currency instead.
        if (null !== $data['currency_id'] || null !== $data['currency_code']) {
            $currency = $this->getCurrency((int)($data['currency_id'] ?? null), (string)($data['currency_code'] ?? null));
            unset($data['currency_code']);
            $data['currency_id'] = $currency->id;
        }

        if (null === $data['currency_id']) {
            $data['currency_id'] = $this->accountRepository->getMetaValue($account, 'currency_id');
        }

        if (null === $data['account_role']) {
            $data['account_role'] = $this->accountRepository->getMetaValue($account, 'account_role');
        }
        // update all meta data:
        $this->updateMetaData($account, $data);

        // has valid initial balance (IB) data?
        $type = $account->accountType;
        // if it can have a virtual balance, it can also have an opening balance.

        if (in_array($type->type, $this->canHaveVirtual, true)) {

            if ($this->validOBData($data)) {
                $this->updateOBGroup($account, $data);
            }

            if (!$this->validOBData($data)) {
                $this->deleteOBGroup($account);
            }
        }

        // update note:
        if (isset($data['notes']) && null !== $data['notes']) {
            $this->updateNote($account, (string)$data['notes']);
        }

        return $account;
    }
}
