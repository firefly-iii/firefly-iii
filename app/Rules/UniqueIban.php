<?php
/**
 * UniqueIban.php
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

namespace FireflyIII\Rules;

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use Illuminate\Contracts\Validation\Rule;
use Log;

/**
 * Class UniqueIban
 */
class UniqueIban implements Rule
{
    /** @var Account */
    private $account;

    /** @var string */
    private $expectedType;

    /**
     * Create a new rule instance.
     *
     * @param Account|null $account
     * @param string|null  $expectedType
     */
    public function __construct(?Account $account, ?string $expectedType)
    {
        $this->account      = $account;
        $this->expectedType = $expectedType;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return trans('validation.unique_iban_for_user');
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string $attribute
     * @param  mixed  $value
     *
     * @return bool
     * @throws FireflyException
     */
    public function passes($attribute, $value)
    {
        if (!auth()->check()) {
            return true; // @codeCoverageIgnore
        }
        if (null === $this->expectedType) {
            return true;
        }
        $maxCounts = [
            AccountType::ASSET   => 0,
            AccountType::EXPENSE => 0,
            AccountType::REVENUE => 0,
        ];
        switch ($this->expectedType) {
            case 'asset':
            case AccountType::ASSET:
                // iban should be unique amongst asset accounts
                // should not be in use with expense or revenue accounts.
                // ie: must be totally unique.
                break;
            case 'expense':
            case AccountType::EXPENSE:
                // should be unique amongst expense and asset accounts.
                // may appear once in revenue accounts
                $maxCounts[AccountType::REVENUE] = 1;
                break;
            case 'revenue':
            case AccountType::REVENUE:
                // should be unique amongst revenue and asset accounts.
                // may appear once in expense accounts
                $maxCounts[AccountType::EXPENSE] = 1;
                break;
            default:

                throw new FireflyException(sprintf('UniqueIban cannot handle type "%s"', $this->expectedType));
        }

        foreach ($maxCounts as $type => $max) {
            $count = 0;
            $query = auth()->user()
                           ->accounts()
                           ->leftJoin('account_types', 'account_types.id', '=', 'accounts.account_type_id')
                           ->where('account_types.type', $type);
            if (null !== $this->account) {
                $query->where('accounts.id', '!=', $this->account->id);
            }
            $result = $query->get(['accounts.*']);
            foreach ($result as $account) {
                if ($account->iban === $value) {
                    $count++;
                }
            }
            if ($count > $max) {
                Log::debug(
                    sprintf(
                        'IBAN "%s" is in use with %d account(s) of type "%s", which is too much for expected type "%s"',
                        $value, $count, $type, $this->expectedType
                    )
                );

                return false;
            }
        }

        return true;
    }
}
