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

declare(strict_types=1);

namespace FireflyIII\Factory;


use FireflyIII\Models\PiggyBank;
use FireflyIII\Repositories\PiggyBank\PiggyBankRepositoryInterface;
use FireflyIII\User;

/**
 * Class PiggyBankFactory
 */
class PiggyBankFactory
{
    /** @var PiggyBankRepositoryInterface */
    private $repository;
    /** @var User */
    private $user;

    /**
     * PiggyBankFactory constructor.
     */
    public function __construct()
    {
        $this->repository = app(PiggyBankRepositoryInterface::class);
    }

    /**
     * @param int|null    $piggyBankId
     * @param null|string $piggyBankName
     *
     * @return PiggyBank|null
     */
    public function find(?int $piggyBankId, ?string $piggyBankName): ?PiggyBank
    {
        $piggyBankId   = intval($piggyBankId);
        $piggyBankName = strval($piggyBankName);
        if (strlen($piggyBankName) === 0 && $piggyBankId === 0) {
            return null;
        }
        // first find by ID:
        if ($piggyBankId > 0) {
            /** @var PiggyBank $piggyBank */
            $piggyBank = $this->repository->find($piggyBankId);
            if (!is_null($piggyBank)) {
                return $piggyBank;
            }
        }

        // then find by name:
        if (strlen($piggyBankName) > 0) {
            /** @var PiggyBank $piggyBank */
            $piggyBank = $this->repository->findByName($piggyBankName);
            if (!is_null($piggyBank)) {
                return $piggyBank;
            }
        }

        return null;

    }

    /**
     * @param User $user
     */
    public function setUser(User $user)
    {
        $this->user = $user;
        $this->repository->setUser($user);

    }

}