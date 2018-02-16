<?php
/**
 * TransactionTypeRepository.php
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

namespace FireflyIII\Repositories\TransactionType;
use FireflyIII\Models\TransactionType;

/**
 * Class TransactionTypeRepository
 */
class TransactionTypeRepository implements TransactionTypeRepositoryInterface
{

    /**
     * Find a transaction type or return NULL.
     *
     * @param string $type
     *
     * @return TransactionType|null
     */
    public function findByType(string $type): ?TransactionType
    {
        return TransactionType::where('type', ucfirst($type))->first();
    }
}