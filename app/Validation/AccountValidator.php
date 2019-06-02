<?php
/**
 * AccountValidator.php
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

namespace FireflyIII\Validation;

use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\User;
use Log;

/**
 * Class AccountValidator
 */
class AccountValidator
{
    /** @var bool */
    public $createMode;
    /** @var string */
    public $destError;
    /** @var Account */
    public $destination;
    /** @var Account */
    public $source;
    /** @var string */
    public $sourceError;
    /** @var AccountRepositoryInterface */
    private $accountRepository;
    /** @var array */
    private $combinations;
    /** @var string */
    private $transactionType;
    /** @var User */
    private $user;

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
     * @param int|null $destinationId
     * @param          $destinationName
     *
     * @return bool
     */
    public function validateDestination(?int $destinationId, $destinationName): bool
    {

        Log::debug(sprintf('Now in AccountValidator::validateDestination(%d, "%s")', $destinationId, $destinationName));
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
                $result = $this->validateWithdrawalDestination($destinationId, $destinationName);
                break;
            case TransactionType::DEPOSIT:
                $result = $this->validateDepositDestination($destinationId, $destinationName);
                break;
            case TransactionType::TRANSFER:
                $result = $this->validateTransferDestination($destinationId, $destinationName);
                break;
            //case TransactionType::OPENING_BALANCE:
            //case TransactionType::RECONCILIATION:
            //    die(sprintf('Cannot handle type "%s"', $this->transactionType));
        }

        return $result;
    }

    /**
     * @param int|null    $accountId
     * @param string|null $accountName
     *
     * @return bool
     */
    public function validateSource(?int $accountId, ?string $accountName): bool
    {
        Log::debug(sprintf('Now in AccountValidator::validateSource(%d, "%s")', $accountId, $accountName));
        switch ($this->transactionType) {
            default:
                $result            = false;
                $this->sourceError = sprintf('Cannot handle type "%s" :(', $this->transactionType);
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
            //case TransactionType::OPENING_BALANCE:
            //case TransactionType::RECONCILIATION:
            //    die(sprintf('Cannot handle type "%s"', $this->transactionType));
        }

        return $result;
    }

    /**
     * @param string $accountType
     *
     * @return bool
     */
    private function canCreateType(string $accountType): bool
    {
        $result = false;
        switch ($accountType) {
            default:
                Log::error(sprintf('AccountValidator::validateSource cannot handle "%s".', $this->transactionType));
                break;
            case AccountType::ASSET:
            case AccountType::LOAN:
            case AccountType::MORTGAGE:
            case AccountType::DEBT:
                $result = false;
                break;
            case AccountType::EXPENSE:
            case AccountType::REVENUE:
                $result = true;
                break;
        }

        return $result;
    }

    /**
     * @param array $accountTypes
     *
     * @return bool
     */
    private function canCreateTypes(array $accountTypes): bool
    {
        /** @var string $accountType */
        foreach ($accountTypes as $accountType) {
            if ($this->canCreateType($accountType)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array       $validTypes
     * @param int|null    $accountId
     * @param string|null $accountName
     *
     * @return Account|null
     */
    private function findExistingAccount(array $validTypes, int $accountId, string $accountName): ?Account
    {
        $result = null;

        // find by ID
        if ($accountId > 0) {
            $first = $this->accountRepository->findNull($accountId);
            if ((null !== $first) && in_array($first->accountType->type, $validTypes, true)) {
                $result = $first;
            }
        }

        // find by name:
        if (null === $result && '' !== $accountName) {
            $second = $this->accountRepository->findByName($accountName, $validTypes);
            if (null !== $second) {
                $result = $second;
            }
        }

        return $result;
    }

    /**
     * @param int|null $accountId
     * @param          $accountName
     *
     * @return bool
     */
    private function validateDepositDestination(?int $accountId, $accountName): bool
    {
        $result = null;
        Log::debug(sprintf('Now in validateDepositDestination(%d, "%s")', $accountId, $accountName));

        // source can be any of the following types.
        $validTypes = $this->combinations[$this->transactionType][$this->source->accountType->type] ?? [];
        if (null === $accountId && null === $accountName && false === $this->canCreateTypes($validTypes)) {
            // if both values are NULL we return false,
            // because the destination of a deposit can't be created.
            $this->destError = (string)trans('validation.deposit_dest_need_data');
            Log::error('Both values are NULL, cant create deposit destination.');
            $result = false;
        }
        // if the account can be created anyway we don't need to search.
        if (null === $result && true === $this->canCreateTypes($validTypes)) {
            Log::debug('Can create some of these types, so return true.');
            $result = true;
        }

        if (null === $result) {
            // otherwise try to find the account:
            $search = $this->findExistingAccount($validTypes, (int)$accountId, (string)$accountName);
            if (null === $search) {
                Log::debug('findExistingAccount() returned NULL, so the result is false.');
                $this->destError = (string)trans('validation.deposit_dest_bad_data', ['id' => $accountId, 'name' => $accountName]);
                $result          = false;
            }
            if (null !== $search) {
                Log::debug(sprintf('findExistingAccount() returned #%d ("%s"), so the result is true.', $search->id, $search->name));
                $this->destination = $search;
                $result            = true;
            }
        }
        $result = $result ?? false;
        Log::debug(sprintf('validateDepositDestination(%d, "%s") will return %s', $accountId, $accountName, var_export($result, true)));

        return $result;
    }

    /**
     * @param int|null    $accountId
     * @param string|null $accountName
     *
     * @return bool
     */
    private function validateDepositSource(?int $accountId, ?string $accountName): bool
    {
        Log::debug(sprintf('Now in validateDepositSource(%d, "%s")', $accountId, $accountName));
        $result = null;
        // source can be any of the following types.
        $validTypes = array_keys($this->combinations[$this->transactionType]);
        if (null === $accountId && null === $accountName && false === $this->canCreateTypes($validTypes)) {
            // if both values are NULL return false,
            // because the source of a deposit can't be created.
            // (this never happens).
            $this->sourceError = (string)trans('validation.deposit_source_need_data');
            $result            = false;
        }

        // if the user submits an ID only but that ID is not of the correct type,
        // return false.
        if (null !== $accountId && null === $accountName) {
            $search = $this->accountRepository->findNull($accountId);
            if (null !== $search && !in_array($search->accountType->type, $validTypes, true)) {
                Log::debug(sprintf('User submitted only an ID (#%d), which is a "%s", so this is not a valid source.', $accountId, $search->accountType->type));
                $result = false;
            }
        }

        // if the account can be created anyway we don't need to search.
        if (null === $result && true === $this->canCreateTypes($validTypes)) {
            $result = true;

            // set the source to be a (dummy) revenue account.
            $account              = new Account;
            $accountType          = AccountType::whereType(AccountType::REVENUE)->first();
            $account->accountType = $accountType;
            $this->source         = $account;
        }
        $result = $result ?? false;

        // don't expect to end up here:
        return $result;
    }

    /**
     * @param int|null $accountId
     * @param          $accountName
     *
     * @return bool
     */
    private function validateTransferDestination(?int $accountId, $accountName): bool
    {
        Log::debug(sprintf('Now in validateTransferDestination(%d, "%s")', $accountId, $accountName));
        // source can be any of the following types.
        $validTypes = $this->combinations[$this->transactionType][$this->source->accountType->type] ?? [];
        if (null === $accountId && null === $accountName && false === $this->canCreateTypes($validTypes)) {
            // if both values are NULL we return false,
            // because the destination of a transfer can't be created.
            $this->destError = (string)trans('validation.transfer_dest_need_data');
            Log::error('Both values are NULL, cant create transfer destination.');

            return false;
        }

        // otherwise try to find the account:
        $search = $this->findExistingAccount($validTypes, (int)$accountId, (string)$accountName);
        if (null === $search) {
            $this->destError = (string)trans('validation.transfer_dest_bad_data', ['id' => $accountId, 'name' => $accountName]);

            return false;
        }
        $this->destination = $search;

        return true;
    }

    /**
     * @param int|null    $accountId
     * @param string|null $accountName
     *
     * @return bool
     */
    private function validateTransferSource(?int $accountId, ?string $accountName): bool
    {
        Log::debug(sprintf('Now in validateTransferSource(%d, "%s")', $accountId, $accountName));
        // source can be any of the following types.
        $validTypes = array_keys($this->combinations[$this->transactionType]);
        if (null === $accountId && null === $accountName && false === $this->canCreateTypes($validTypes)) {
            // if both values are NULL we return false,
            // because the source of a withdrawal can't be created.
            $this->sourceError = (string)trans('validation.transfer_source_need_data');

            return false;
        }

        // otherwise try to find the account:
        $search = $this->findExistingAccount($validTypes, (int)$accountId, (string)$accountName);
        if (null === $search) {
            $this->sourceError = (string)trans('validation.transfer_source_bad_data', ['id' => $accountId, 'name' => $accountName]);

            return false;
        }
        $this->source = $search;

        return true;
    }

    /**
     * @param int|null    $accountId
     * @param string|null $accountName
     *
     * @return bool
     */
    private function validateWithdrawalDestination(?int $accountId, ?string $accountName): bool
    {
        Log::debug(sprintf('Now in validateWithdrawalDestination(%d, "%s")', $accountId, $accountName));
        // source can be any of the following types.
        $validTypes = $this->combinations[$this->transactionType][$this->source->accountType->type] ?? [];
        if (null === $accountId && null === $accountName && false === $this->canCreateTypes($validTypes)) {
            // if both values are NULL return false,
            // because the destination of a withdrawal can never be created automatically.
            $this->destError = (string)trans('validation.withdrawal_dest_need_data');

            return false;
        }

        // if the account can be created anyway don't need to search.
        if (true === $this->canCreateTypes($validTypes)) {

            return true;
        }

        // don't expect to end up here:
        return false;
    }

    /**
     * @param int|null    $accountId
     * @param string|null $accountName
     *
     * @return bool
     */
    private function validateWithdrawalSource(?int $accountId, ?string $accountName): bool
    {
        Log::debug(sprintf('Now in validateWithdrawalSource(%d, "%s")', $accountId, $accountName));
        // source can be any of the following types.
        $validTypes = array_keys($this->combinations[$this->transactionType]);
        if (null === $accountId && null === $accountName && false === $this->canCreateTypes($validTypes)) {
            // if both values are NULL we return false,
            // because the source of a withdrawal can't be created.
            $this->sourceError = (string)trans('validation.withdrawal_source_need_data');

            return false;
        }

        // otherwise try to find the account:
        $search = $this->findExistingAccount($validTypes, (int)$accountId, (string)$accountName);
        if (null === $search) {
            $this->sourceError = (string)trans('validation.withdrawal_source_bad_data', ['id' => $accountId, 'name' => $accountName]);

            return false;
        }
        $this->source = $search;

        return true;
    }


}