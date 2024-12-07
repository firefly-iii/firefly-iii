<?php

/**
 * AccountForm.php
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

namespace FireflyIII\Support\Form;

use FireflyIII\Enums\AccountTypeEnum;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;

/**
 * Class AccountForm
 *
 * All form methods that are account related.
 *
 * TODO describe all methods
 * TODO optimize repositories and methods.
 */
class AccountForm
{
    use FormSupport;

    /**
     * Grouped dropdown list of all accounts that are valid as the destination of a withdrawal.
     */
    public function activeDepositDestinations(string $name, mixed $value = null, ?array $options = null): string
    {
        $types                    = [AccountType::MORTGAGE, AccountType::DEBT, AccountType::CREDITCARD, AccountType::LOAN, AccountType::REVENUE];
        $repository               = $this->getAccountRepository();
        $grouped                  = $this->getAccountsGrouped($types, $repository);
        $cash                     = $repository->getCashAccount();
        $key                      = (string)trans('firefly.cash_account_type');
        $grouped[$key][$cash->id] = sprintf('(%s)', (string)trans('firefly.cash'));

        return $this->select($name, $grouped, $value, $options);
    }

    private function getAccountsGrouped(array $types, ?AccountRepositoryInterface $repository = null): array
    {
        if (null === $repository) {
            $repository = $this->getAccountRepository();
        }
        $accountList    = $repository->getActiveAccountsByType($types);
        $liabilityTypes = [AccountType::MORTGAGE, AccountType::DEBT, AccountType::CREDITCARD, AccountType::LOAN];
        $grouped        = [];

        /** @var Account $account */
        foreach ($accountList as $account) {
            $role                        = (string)$repository->getMetaValue($account, 'account_role');
            if (in_array($account->accountType->type, $liabilityTypes, true)) {
                $role = sprintf('l_%s', $account->accountType->type);
            }
            if ('' === $role) {
                $role = 'no_account_type';
                if (AccountType::EXPENSE === $account->accountType->type) {
                    $role = 'expense_account';
                }
                if (AccountType::REVENUE === $account->accountType->type) {
                    $role = 'revenue_account';
                }
            }
            $key                         = (string)trans(sprintf('firefly.opt_group_%s', $role));
            $grouped[$key][$account->id] = $account->name;
        }

        return $grouped;
    }

    /**
     * Grouped dropdown list of all accounts that are valid as the destination of a withdrawal.
     */
    public function activeWithdrawalDestinations(string $name, mixed $value = null, ?array $options = null): string
    {
        $types                    = [AccountType::MORTGAGE, AccountType::DEBT, AccountType::CREDITCARD, AccountType::LOAN, AccountType::EXPENSE];
        $repository               = $this->getAccountRepository();
        $grouped                  = $this->getAccountsGrouped($types, $repository);

        $cash                     = $repository->getCashAccount();
        $key                      = (string)trans('firefly.cash_account_type');
        $grouped[$key][$cash->id] = sprintf('(%s)', (string)trans('firefly.cash'));

        return $this->select($name, $grouped, $value, $options);
    }

    /**
     * Check list of asset accounts.
     *
     * @throws FireflyException
     */
    public function assetAccountCheckList(string $name, ?array $options = null): string
    {
        $options ??= [];
        $label    = $this->label($name, $options);
        $options  = $this->expandOptionArray($name, $label, $options);
        $classes  = $this->getHolderClasses($name);
        $selected = request()->old($name) ?? [];

        // get all asset accounts:
        $types    = [AccountType::ASSET, AccountType::DEFAULT, AccountType::LOAN, AccountType::MORTGAGE, AccountType::DEBT];
        $grouped  = $this->getAccountsGrouped($types);

        unset($options['class']);

        try {
            $html = view('form.assetAccountCheckList', compact('classes', 'selected', 'name', 'label', 'options', 'grouped'))->render();
        } catch (\Throwable $e) {
            app('log')->debug(sprintf('Could not render assetAccountCheckList(): %s', $e->getMessage()));
            $html = 'Could not render assetAccountCheckList.';

            throw new FireflyException($html, 0, $e);
        }

        return $html;
    }

    /**
     * Basic list of asset accounts.
     *
     * @param mixed $value
     */
    public function assetAccountList(string $name, $value = null, ?array $options = null): string
    {
        $types   = [AccountTypeEnum::ASSET->value, AccountTypeEnum::DEFAULT->value];
        $grouped = $this->getAccountsGrouped($types);

        return $this->select($name, $grouped, $value, $options);
    }

    /**
     * Basic list of asset accounts.
     *
     * @param mixed $value
     */
    public function assetLiabilityMultiAccountList(string $name, $value = null, ?array $options = null): string
    {
        $types   = [AccountTypeEnum::ASSET->value, AccountTypeEnum::DEFAULT->value, AccountTypeEnum::MORTGAGE->value, AccountTypeEnum::DEBT->value,AccountTypeEnum::LOAN->value];
        $grouped = $this->getAccountsGrouped($types);
        return $this->multiSelect($name, $grouped, $value, $options);
    }

    /**
     * Same list but all liabilities as well.
     *
     * @param mixed $value
     */
    public function longAccountList(string $name, $value = null, ?array $options = null): string
    {
        $types   = [AccountType::ASSET, AccountType::DEFAULT, AccountType::MORTGAGE, AccountType::DEBT, AccountType::LOAN];
        $grouped = $this->getAccountsGrouped($types);

        return $this->select($name, $grouped, $value, $options);
    }
}
