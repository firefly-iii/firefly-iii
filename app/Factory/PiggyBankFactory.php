<?php
/**
 * PiggyBankFactory.php
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
/** @noinspection MultipleReturnStatementsInspection */
declare(strict_types=1);

namespace FireflyIII\Factory;


use FireflyIII\Models\PiggyBank;
use FireflyIII\User;
use Log;

/**
 * Class PiggyBankFactory
 */
class PiggyBankFactory
{
    /** @var User */
    private $user;

    /**
     * Constructor.
     * @codeCoverageIgnore
     */
    public function __construct()
    {
        if ('testing' === config('app.env')) {
            Log::warning(sprintf('%s should not be instantiated in the TEST environment!', get_class($this)));
        }
    }

    /**
     * @param int|null    $piggyBankId
     * @param null|string $piggyBankName
     *
     * @return PiggyBank|null
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function find(?int $piggyBankId, ?string $piggyBankName): ?PiggyBank
    {
        $piggyBankId   = (int)$piggyBankId;
        $piggyBankName = (string)$piggyBankName;
        if ('' === $piggyBankName && 0 === $piggyBankId) {
            return null;
        }
        // first find by ID:
        if ($piggyBankId > 0) {
            /** @var PiggyBank $piggyBank */
            $piggyBank = $this->user->piggyBanks()->find($piggyBankId);
            if (null !== $piggyBank) {
                return $piggyBank;
            }
        }

        // then find by name:
        if ('' !== $piggyBankName) {
            /** @var PiggyBank $piggyBank */
            $piggyBank = $this->findByName($piggyBankName);
            if (null !== $piggyBank) {
                return $piggyBank;
            }
        }

        return null;

    }

    /**
     * @param string $name
     *
     * @return PiggyBank|null
     */
    public function findByName(string $name): ?PiggyBank
    {
        return $this->user->piggyBanks()->where('name', $name)->first();
    }

    /**
     * @param User $user
     */
    public function setUser(User $user): void
    {
        $this->user = $user;

    }

}
