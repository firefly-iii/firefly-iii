<?php
declare(strict_types=1);
/**
 * AccountFactory.php
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


namespace FireflyIII\Factory;

use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Services\Internal\Support\AccountServiceTrait;
use FireflyIII\User;

/**
 * Factory to create or return accounts.
 *
 * Class AccountFactory
 */
class AccountFactory
{
    use AccountServiceTrait;
    /** @var User */
    private $user;

    /**
     * @param array $data
     *
     * @return Account
     */
    public function create(array $data): Account
    {
        $type         = $this->getAccountType($data['account_type_id'], $data['accountType']);
        $data['iban'] = $this->filterIban($data['iban']);


        // account may exist already:
        $existingAccount = $this->find($data['name'], $type->type);
        if (null !== $existingAccount) {
            return $existingAccount;
        }


        // create it:
        $databaseData
            = [
            'user_id'         => $this->user->id,
            'account_type_id' => $type->id,
            'name'            => $data['name'],
            'virtual_balance' => strlen(strval($data['virtualBalance'])) === 0 ? '0' : $data['virtualBalance'],
            'active'          => true === $data['active'] ? true : false,
            'iban'            => $data['iban'],
        ];

        // remove virtual balance when not an asset account:
        if ($type->type !== AccountType::ASSET) {
            $databaseData['virtual_balance'] = '0';
        }

        $newAccount = Account::create($databaseData);
        $this->updateMetadata($newAccount, $data);

        if ($this->validIBData($data) && $type->type === AccountType::ASSET) {
            $this->updateIB($newAccount, $data);
        }
        if (!$this->validIBData($data) && $type->type === AccountType::ASSET) {
            $this->deleteIB($newAccount);
        }
        // update note:
        if (isset($data['notes'])) {
            $this->updateNote($newAccount, $data['notes']);
        }

        return $newAccount;
    }

    /**
     * @param string $accountName
     * @param string $accountType
     *
     * @return Account|null
     */
    public function find(string $accountName, string $accountType): ?Account
    {
        $type     = AccountType::whereType($accountType)->first();
        $accounts = $this->user->accounts()->where('account_type_id', $type->id)->get(['accounts.*']);

        /** @var Account $object */
        foreach ($accounts as $object) {
            if ($object->name === $accountName) {
                return $object;
            }
        }

        return null;
    }

    /**
     * @param string $accountName
     * @param string $accountType
     *
     * @return Account
     */
    public function findOrCreate(string $accountName, string $accountType): Account
    {
        $type     = AccountType::whereType($accountType)->first();
        $accounts = $this->user->accounts()->where('account_type_id', $type->id)->get(['accounts.*']);

        /** @var Account $object */
        foreach ($accounts as $object) {
            if ($object->name === $accountName) {
                return $object;
            }
        }

        return $this->create(
            [
                'user_id'         => $this->user->id,
                'name'            => $accountName,
                'account_type_id' => $type->id,
                'accountType'     => null,
                'virtualBalance'  => '0',
                'iban'            => null,
                'active'          => true,
            ]
        );
    }

    /**
     * @param User $user
     */
    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    /**
     * @param int|null    $accountTypeId
     * @param null|string $accountType
     *
     * @return AccountType|null
     */
    protected function getAccountType(?int $accountTypeId, ?string $accountType): ?AccountType
    {
        $accountTypeId = intval($accountTypeId);
        if ($accountTypeId > 0) {
            return AccountType::find($accountTypeId);
        }
        $type = config('firefly.accountTypeByIdentifier.' . strval($accountType));

        return AccountType::whereType($type)->first();

    }

}
