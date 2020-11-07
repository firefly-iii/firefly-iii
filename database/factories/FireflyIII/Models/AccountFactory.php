<?php
/*
 * AccountFactory.php
 * Copyright (c) 2020 james@firefly-iii.org
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

namespace Database\Factories\FireflyIII\Models;

use FireflyIII\Models\Account;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Class AccountFactory
 */
class AccountFactory extends Factory
{

    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Account::class;

    /**
     * @inheritDoc
     */
    public function definition()
    {
        return [
            'user_id'         => 1,
            'account_type_id' => 1,
            'name'            => $this->faker->words(3, true),
            'virtual_balance' => '0',
            'active'          => 1,
            'encrypted'       => 0,
            'order'           => 1,
        ];
    }

    /**
     * @return AccountFactory
     */
    public function asset()
    {
        return $this->state(
            function () {
                return [
                    'account_type_id' => 3,
                ];
            }
        );
    }

    /**
     * @return AccountFactory
     */
    public function initialBalance()
    {
        return $this->state(
            function () {
                return [
                    'account_type_id' => 6,
                ];
            }
        );
    }

    /**
     * @return AccountFactory
     */
    public function expense()
    {
        return $this->state(
            function () {
                return [
                    'account_type_id' => 4,
                ];
            }
        );
    }


}
