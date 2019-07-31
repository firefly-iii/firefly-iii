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
     * @codeCoverageIgnore
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
     * @codeCoverageIgnore
     *
     * @return string
     */
    public function message(): string
    {
        return (string)trans('validation.unique_iban_for_user');
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string $attribute
     * @param  mixed  $value
     *
     * @return bool
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function passes($attribute, $value): bool
    {
        if (!auth()->check()) {
            return true; // @codeCoverageIgnore
        }
        if (null === $this->expectedType) {
            return true; // @codeCoverageIgnore
        }
        $maxCounts = $this->getMaxOccurrences();

        foreach ($maxCounts as $type => $max) {
            $count = $this->countHits($type, $value);
            Log::debug(sprintf('Count for "%s" and IBAN "%s" is %d', $type, $value, $count));
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

    /**
     * @param string $type
     * @param string $iban
     *
     * @return int
     */
    private function countHits(string $type, string $iban): int
    {
        $query
            = auth()->user()
                    ->accounts()
                    ->leftJoin('account_types', 'account_types.id', '=', 'accounts.account_type_id')
                    ->where('accounts.iban', $iban)
                    ->where('account_types.type', $type);

        if (null !== $this->account) {
            $query->where('accounts.id', '!=', $this->account->id);
        }

        return $query->count();
    }

    /**
     * @return array
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function getMaxOccurrences(): array
    {
        $maxCounts = [
            AccountType::ASSET   => 0,
            AccountType::EXPENSE => 0,
            AccountType::REVENUE => 0,
        ];

        if ('expense' === $this->expectedType || AccountType::EXPENSE === $this->expectedType) {
            // IBAN should be unique amongst expense and asset accounts.
            // may appear once in revenue accounts
            $maxCounts[AccountType::REVENUE] = 1;
        }
        if ('revenue' === $this->expectedType || AccountType::EXPENSE === $this->expectedType) {
            // IBAN should be unique amongst revenue and asset accounts.
            // may appear once in expense accounts
            $maxCounts[AccountType::EXPENSE] = 1;
        }

        return $maxCounts;
    }
}
