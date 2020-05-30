<?php
declare(strict_types=1);
/**
 * AccountValidatorProperties.php
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

namespace FireflyIII\Validation\Account;

use FireflyIII\Models\Account;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\User;

/**
 * Trait AccountValidatorProperties
 */
trait AccountValidatorProperties
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
}
