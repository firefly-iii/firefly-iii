<?php
/*
 * JournalRepository.php
 * Copyright (c) 2023 james@firefly-iii.org
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

namespace FireflyIII\Repositories\UserGroups\Journal;

use FireflyIII\Support\Repositories\UserGroup\UserGroupTrait;
use Illuminate\Support\Collection;

/**
 * Class JournalRepository
 */
class JournalRepository implements JournalRepositoryInterface
{
    use UserGroupTrait;

    public function searchJournalDescriptions(string $search, int $limit): Collection
    {
        $query = $this->userGroup->transactionJournals()
            ->orderBy('date', 'DESC')
        ;
        if ('' !== $search) {
            $query->where('description', 'LIKE', sprintf('%%%s%%', $search));
        }

        return $query->take($limit)->get();
    }
}
