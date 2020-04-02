<?php
/**
 * AccountValidator.php
 * Copyright (c) 2019 james@firefly-iii.org
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace FireflyIII\Validation;

use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\User;
use FireflyIII\Validation\Account\AccountValidatorProperties;
use FireflyIII\Validation\Account\DepositValidation;
use FireflyIII\Validation\Account\OBValidation;
use FireflyIII\Validation\Account\ReconciliationValidation;
use FireflyIII\Validation\Account\TransferValidation;
use FireflyIII\Validation\Account\WithdrawalValidation;
use Log;

/**
 * Class AccountValidator
 */
class AccountValidator
{
    use AccountValidatorProperties, WithdrawalValidation, DepositValidation, TransferValidation, ReconciliationValidation, OBValidation;

    /**
     * AccountValidator constructor.
     */
    public function __construct()
    {
        $this->createMode   = false;
        $this->destError    = 'No error yet.';
        $this->sourceError  = 'No error yet.';
        $this->combinations = config('firefly.source_dests');

        /** @var AccountRepositoryInterface accountRepository */
        $this->accountRepository = app(AccountRepositoryInterface::class);

        if ('testing' === config('app.env')) {
            Log::warning(sprintf('%s should not be instantiated in the TEST environment!', get_class($this)));
        }
    }

    /**
     * @param string $transactionType
     */
    public function setTransactionType(string $transactionType): void
    {
        Log::debug(sprintf('Transaction type for validator is now %s', ucfirst($transactionType)));
        $this->transactionType = ucfirst($transactionType);
    }

    /**
     * @param User $user
     */
    public function setUser(User $user): void
    {
        $this->user = $user;
        $this->accountRepository->setUser($user);
    }

    /**
     * @param int|null    $accountId
     * @param string|null $accountName
     * @param string|null $accountIban
     *
     * @return bool
     */
    public function validateDestination(?int $accountId, ?string $accountName, ?string $accountIban): bool
    {
        Log::debug(sprintf('Now in AccountValidator::validateDestination(%d, "%s", "%s")', $accountId, $accountName, $accountIban));
        if (null === $this->source) {
            Log::error('Source is NULL, always FALSE.');
            $this->destError = 'No source account validation has taken place yet. Please do this first or overrule the object.';

            return false;
        }
        switch ($this->transactionType) {
            default:
                $this->destError = sprintf('AccountValidator::validateDestination cannot handle "%s", so it will always return false.', $this->transactionType);
                Log::error(sprintf('AccountValidator::validateDestination cannot handle "%s", so it will always return false.', $this->transactionType));

                $result = false;
                break;

            case TransactionType::WITHDRAWAL:
                $result = $this->validateWithdrawalDestination($accountId, $accountName);
                break;
            case TransactionType::DEPOSIT:
                $result = $this->validateDepositDestination($accountId, $accountName);
                break;
            case TransactionType::TRANSFER:
                $result = $this->validateTransferDestination($accountId, $accountName);
                break;
            case TransactionType::OPENING_BALANCE:
                $result = $this->validateOBDestination($accountId, $accountName);
                break;
            case TransactionType::RECONCILIATION:
                $result = $this->validateReconciliationDestination($accountId);
                break;
        }

        return $result;
    }

    /**
     * @param int|null    $accountId
     * @param string|null $accountName
     * @param string|null $accountIban
     *
     * @return bool
     */
    public function validateSource(?int $accountId, ?string $accountName, ?string $accountIban): bool
    {
        Log::debug(sprintf('Now in AccountValidator::validateSource(%d, "%s", "%s")', $accountId, $accountName, $accountIban));
        switch ($this->transactionType) {
            default:
                $result            = false;
                $this->sourceError = 'Firefly III cannot validate the account information you submitted.';
                Log::error(sprintf('AccountValidator::validateSource cannot handle "%s", so it will always return false.', $this->transactionType));
                break;
            case TransactionType::WITHDRAWAL:
                $result = $this->validateWithdrawalSource($accountId, $accountName);
                break;
            case TransactionType::DEPOSIT:
                $result = $this->validateDepositSource($accountId, $accountName);
                break;
            case TransactionType::TRANSFER:
                $result = $this->validateTransferSource($accountId, $accountName);
                break;
            case TransactionType::OPENING_BALANCE:
                $result = $this->validateOBSource($accountId, $accountName);
                break;
            case TransactionType::RECONCILIATION:
                Log::debug('Calling validateReconciliationSource');
                $result = $this->validateReconciliationSource($accountId);
                break;
        }

        return $result;
    }

    /**
     * @param string $accountType
     *
     * @return bool
     */
    protected function canCreateType(string $accountType): bool
    {
        $canCreate = [AccountType::EXPENSE, AccountType::REVENUE, AccountType::INITIAL_BALANCE];
        if (in_array($accountType, $canCreate, true)) {
            return true;
        }

        return false;
    }

    /**
     * @param array $accountTypes
     *
     * @return bool
     */
    protected function canCreateTypes(array $accountTypes): bool
    {
        Log::debug('Can we create any of these types?', $accountTypes);
        /** @var string $accountType */
        foreach ($accountTypes as $accountType) {
            if ($this->canCreateType($accountType)) {
                Log::debug(sprintf('YES, we can create a %s', $accountType));

                return true;
            }
        }
        Log::debug('NO, we cant create any of those.');

        return false;
    }

    /**
     * @param array       $validTypes
     * @param int $accountId
     * @param string $accountName
     *
     * @return Account|null
     */
    protected function findExistingAccount(array $validTypes, int $accountId, string $accountName): ?Account
    {
        // find by ID
        if ($accountId > 0) {
            $first = $this->accountRepository->findNull($accountId);
            if ((null !== $first) && in_array($first->accountType->type, $validTypes, true)) {
                return $first;
            }
        }

        // find by name:
        if ('' !== $accountName) {
            return $this->accountRepository->findByName($accountName, $validTypes);
        }

        return null;
    }


}
