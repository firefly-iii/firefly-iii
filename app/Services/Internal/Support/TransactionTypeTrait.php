<?php
/**
 * TransactionTypeTrait.php
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

namespace FireflyIII\Services\Internal\Support;

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Factory\TransactionTypeFactory;
use FireflyIII\Models\TransactionType;
use Log;

/**
 * Trait TransactionTypeTrait
 *
 */
trait TransactionTypeTrait
{
    /**
     * Get the transaction type. Since this is mandatory, will throw an exception when nothing comes up. Will always
     * use TransactionType repository.
     *
     * @param string $type
     *
     * @return TransactionType
     * @throws FireflyException
     */
    protected function findTransactionType(string $type): TransactionType
    {
        $factory         = app(TransactionTypeFactory::class);
        $transactionType = $factory->find($type);
        if (null === $transactionType) {
            Log::error(sprintf('Could not find transaction type for "%s"', $type)); // @codeCoverageIgnore
            throw new FireflyException(sprintf('Could not find transaction type for "%s"', $type)); // @codeCoverageIgnore
        }

        return $transactionType;
    }
}
