<?php
/**
 * BalanceEntry.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
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

namespace FireflyIII\Helpers\Collection;

use FireflyIII\Models\Account as AccountModel;

/**
 * Class BalanceEntry.
 *
 * @codeCoverageIgnore
 */
class BalanceEntry
{
    /** @var AccountModel The account. */
    protected $account;
    /** @var string The amount left. */
    protected $left = '0';
    /** @var string The amount spent. */
    protected $spent = '0';

    /**
     * Account getter.
     *
     * @return AccountModel
     */
    public function getAccount(): AccountModel
    {
        return $this->account;
    }

    /**
     * Account setter.
     *
     * @param AccountModel $account
     */
    public function setAccount(AccountModel $account): void
    {
        $this->account = $account;
    }

    /**
     * Get amount left.
     *
     * @return string
     */
    public function getLeft(): string
    {
        return $this->left;
    }

    /**
     * Set amount left.
     *
     * @param string $left
     */
    public function setLeft(string $left): void
    {
        $this->left = $left;
    }

    /**
     * Get amount spent.
     *
     * @return string
     */
    public function getSpent(): string
    {
        return $this->spent;
    }

    /**
     * Set amount spent.
     *
     * @param string $spent
     */
    public function setSpent(string $spent): void
    {
        $this->spent = $spent;
    }
}
