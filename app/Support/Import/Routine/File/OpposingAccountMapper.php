<?php
/**
 * OpposingAccountMapper.php
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
 * Class OpposingAccountMapper
 */
class OpposingAccountMapper
{
    /** @var AccountRepositoryInterface */
    private $repository;
    /** @var User */
    private $user;

    /**
     * @param int|null $accountId
     * @param string   $amount
     * @param array    $data
     *
     * @return Account
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function map(?int $accountId, string $amount, array $data): Account
    {
        Log::debug('Now in OpposingAccountMapper::map()');
        // default assumption is we're looking for an expense account.
        $expectedType = AccountType::EXPENSE;
        $result       = null;
        Log::debug(sprintf('Going to search for accounts of type %s', $expectedType));
        if (1 === bccomp($amount, '0')) {
            // more than zero.
            $expectedType = AccountType::REVENUE;
            Log::debug(sprintf('Because amount is %s, will instead search for accounts of type %s', $amount, $expectedType));
        }

        // append expected types with liability types:
        $expectedTypes = [$expectedType, AccountType::LOAN, AccountType::DEBT, AccountType::MORTGAGE];


        if ((int)$accountId > 0) {
            // find any account with this ID:
            $result = $this->repository->findNull($accountId);
            if (null !== $result && (in_array($result->accountType->type, $expectedTypes, true) || $result->accountType->type === AccountType::ASSET)) {
                Log::debug(sprintf('Found account "%s" (%s) based on given ID %d. Return it!', $result->name, $result->accountType->type, $accountId));

                return $result;
            }
            if (null !== $result && !in_array($result->accountType->type, $expectedTypes, true)) {
                Log::warning(
                    sprintf(
                        'Found account "%s" (%s) based on given ID %d, but need a %s. Return nothing.', $result->name, $result->accountType->type, $accountId,
                        $expectedType
                    )
                );
            }
        }
        // if result is not null, system has found an account
        // but it's of the wrong type. If we dont have a name, use
        // the result's name, iban in the search below.
        if (null !== $result && '' === (string)($data['name'] ?? '')) {
            Log::debug(sprintf('Will search for account with name "%s" instead of NULL.', $result->name));
            $data['name'] = $result->name;
        }
        if (null !== $result && '' !== (string)$result->iban && '' === ($data['iban'] ?? '')) {
            Log::debug(sprintf('Will search for account with IBAN "%s" instead of NULL.', $result->iban));
            $data['iban'] = $result->iban;
        }

        // first search for $expectedType, then find asset:
        $searchTypes = [$expectedType, AccountType::ASSET, AccountType::DEBT, AccountType::MORTGAGE, AccountType::LOAN];
        foreach ($searchTypes as $type) {
            // find by (respectively):
            // IBAN, accountNumber, name,
            $fields = ['iban' => 'findByIbanNull', 'number' => 'findByAccountNumber', 'name' => 'findByName'];
            foreach ($fields as $field => $function) {
                $value = (string)($data[$field] ?? '');
                if ('' === $value) {
                    Log::debug(sprintf('Array does not contain a value for %s. Continue', $field));
                    continue;
                }
                Log::debug(sprintf('Will search for account of type "%s" using %s() and argument "%s".', $type, $function, $value));
                $result = $this->repository->$function($value, [$type]);
                if (null !== $result) {
                    Log::debug(sprintf('Found result: Account #%d, named "%s"', $result->id, $result->name));

                    return $result;
                }
            }
        }
        // not found? Create it!
        $creation = [
            'name'            => $data['name'] ?? '(no name)',
            'iban'            => $data['iban'] ?? null,
            'account_number'  => $data['number'] ?? null,
            'account_type_id' => null,
            'account_type'    => $expectedType,
            'active'          => true,
            'BIC'             => $data['bic'] ?? null,
        ];
        Log::debug('Will try to store a new account: ', $creation);

        return $this->repository->store($creation);
    }

    /**
     * @param User $user
     */
    public function setUser(User $user): void
    {
        $this->user       = $user;
        $this->repository = app(AccountRepositoryInterface::class);
        $this->repository->setUser($user);

    }
}
