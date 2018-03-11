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
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Collection;

/**
 * Class UniqueIban
 */
class UniqueIban implements Rule
{
    /** @var Account */
    private $account;

    /**
     * Create a new rule instance.
     *
     * @param Account|null $account
     */
    public function __construct(?Account $account)
    {
        $this->account = $account;
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
     */
    public function passes($attribute, $value)
    {
        if (!auth()->check()) {
            return true; // @codeCoverageIgnore
        }

        $query = auth()->user()->accounts();
        if (!is_null($this->account)) {
            $query->where('accounts.id', '!=', $this->account->id);
        }

        /** @var Collection $accounts */
        $accounts = $query->get();

        /** @var Account $account */
        foreach ($accounts as $account) {
            if ($account->iban === $value) {
                return false;
            }
        }

        return true;
    }
}
