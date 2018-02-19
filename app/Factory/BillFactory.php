<?php
/**
 * BillFactory.php
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

use FireflyIII\Models\Bill;
use FireflyIII\Repositories\Bill\BillRepositoryInterface;
use FireflyIII\User;

/**
 * Class BillFactory
 */
class BillFactory
{
    /** @var BillRepositoryInterface */
    private $repository;
    /** @var User */
    private $user;

    /**
     * BillFactory constructor.
     */
    public function __construct()
    {
        $this->repository = app(BillRepositoryInterface::class);
    }

    /**
     * @param int|null    $billId
     * @param null|string $billName
     *
     * @return Bill|null
     */
    public function find(?int $billId, ?string $billName): ?Bill
    {
        $billId   = intval($billId);
        $billName = strval($billName);
        // first find by ID:
        if ($billId > 0) {
            /** @var Bill $bill */
            $bill = $this->repository->find($billId);
            if (!is_null($bill)) {
                return $bill;
            }
        }

        // then find by name:
        if (strlen($billName) > 0) {
            $bill = $this->repository->findByName($billName);
            if (!is_null($bill)) {
                return $bill;
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