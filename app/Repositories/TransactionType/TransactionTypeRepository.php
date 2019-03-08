<?php
/**
 * TransactionTypeRepository.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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

namespace FireflyIII\Repositories\TransactionType;

use FireflyIII\Models\TransactionType;

/**
 * Class TransactionTypeRepository
 */
class TransactionTypeRepository implements TransactionTypeRepositoryInterface
{

    /**
     * @param string $type
     *
     * @return TransactionType|null
     */
    public function findByType(string $type): ?TransactionType
    {
        $search = ucfirst($type);

        return TransactionType::whereType($search)->first();
    }

    /**
     * @param TransactionType|null $type
     * @param string|null          $typeString
     *
     * @return TransactionType
     */
    public function findTransactionType(?TransactionType $type, ?string $typeString): TransactionType
    {
        if (null !== $type) {
            return $type;
        }
        $search = $this->findByType($typeString);
        if (null === $search) {
            $search = $this->findByType(TransactionType::WITHDRAWAL);
        }

        return $search;
    }
}