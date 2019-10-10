<?php

/**
 * UpdatedTransactionGroup.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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

namespace FireflyIII\Events;

use FireflyIII\Models\TransactionGroup;
use Illuminate\Queue\SerializesModels;

/**
 * Class UpdatedTransactionGroup.
 *
 * @codeCoverageIgnore
 *
 */
class UpdatedTransactionGroup extends Event
{
    use SerializesModels;

    /** @var TransactionGroup The group that was stored. */
    public $transactionGroup;

    /**
     * Create a new event instance.
     *
     * @param TransactionGroup $transactionGroup
     */
    public function __construct(TransactionGroup $transactionGroup)
    {
        $this->transactionGroup = $transactionGroup;
    }
}
