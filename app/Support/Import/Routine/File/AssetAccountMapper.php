<?php
/**
 * AssetAccountMapper.php
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

namespace FireflyIII\Support\Import\Routine\File;

use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\User;
use Log;

/**
 * Class AssetAccountMapper
 * Can also handle liability accounts.
 */
class AssetAccountMapper
{
    /** @var int */
    private $defaultAccount;
    /** @var AccountRepositoryInterface */
    private $repository;
    /** @var User */
    private $user;

    /** @var array */
    private $types;

    /**
     * AssetAccountMapper constructor.
     */
    public function __construct()
    {
        $this->types = [AccountType::ASSET, AccountType::LOAN, AccountType::DEBT, AccountType::MORTGAGE];
    }

    /**
     * Based upon data in the importable, try to find or create the asset account account.
     *
     * @param int|null $accountId
     * @param array    $data
     *
     * @return Account
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function map(?int $accountId, array $data): Account
    {
        Log::debug(sprintf('Now in AssetAccountMapper::map(%d)', $accountId), $data);
        if ((int)$accountId > 0) {
            // find asset account with this ID:
            $result = $this->repository->findNull($accountId);
            if (null !== $result && in_array($result->accountType->type, $this->types, true)) {
                Log::debug(sprintf('Found %s "%s" based on given ID %d', $result->accountType->type, $result->name, $accountId));

                return $result;
            }
            if (null !== $result && in_array($result->accountType->type, $this->types, true)) {
                Log::warning(
                    sprintf('Found account "%s" based on given ID %d but its a %s, return nothing.', $result->name, $accountId, $result->accountType->type)
                );
            }
        }
        // find by (respectively):
        // IBAN, accountNumber, name,
        $fields = ['iban' => 'findByIbanNull', 'number' => 'findByAccountNumber', 'name' => 'findByName'];
        foreach ($fields as $field => $function) {
            $value = (string)($data[$field] ?? '');
            if ('' === $value) {
                Log::debug(sprintf('Array does not contain a value for %s. Continue', $field));
                continue;
            }
            $result = $this->repository->$function($value, $this->types);
            Log::debug(sprintf('Going to run %s() with argument "%s" (asset account or liability)', $function, $value));
            if (null !== $result) {
                Log::debug(sprintf('Found asset account "%s". Return it!', $result->name));

                return $result;
            }
        }
        Log::debug('Found nothing. Will return default account.');
        // still NULL? Return default account.
        $default = null;
        if ($this->defaultAccount > 0) {
            $default = $this->repository->findNull($this->defaultAccount);
        }
        if (null === $default) {
            Log::debug('Default account is NULL! Simply result first account in system.');
            $default = $this->repository->getAccountsByType([AccountType::ASSET])->first();
        }

        Log::debug(sprintf('Return default account "%s" (#%d). Return it!', $default->name, $default->id));

        return $default;
    }

    /**
     * @param int $defaultAccount
     */
    public function setDefaultAccount(int $defaultAccount): void
    {
        $this->defaultAccount = $defaultAccount;
    }

    /**
     * @param User $user
     */
    public function setUser(User $user): void
    {
        $this->user           = $user;
        $this->repository     = app(AccountRepositoryInterface::class);
        $this->defaultAccount = 0;
        $this->repository->setUser($user);

    }
}
