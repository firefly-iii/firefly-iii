<?php

/**
 * AccountSearch.php
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

namespace FireflyIII\Support\Search;

use FireflyIII\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

use function Safe\json_encode;

/**
 * Class AccountSearch
 */
class AccountSearch implements GenericSearchInterface
{
    /** @var string */
    public const string SEARCH_ALL    = 'all';

    /** @var string */
    public const string SEARCH_IBAN   = 'iban';

    /** @var string */
    public const string SEARCH_ID     = 'id';

    /** @var string */
    public const string SEARCH_NAME   = 'name';

    /** @var string */
    public const string SEARCH_NUMBER = 'number';
    private string $field;
    private string $query;
    private array  $types;
    private User   $user;

    public function __construct()
    {
        $this->types = [];
    }

    public function search(): Collection
    {
        $searchQuery   = $this->user->accounts()
            ->leftJoin('account_types', 'accounts.account_type_id', '=', 'account_types.id')
            ->leftJoin('account_meta', 'accounts.id', '=', 'account_meta.account_id')
            ->whereIn('account_types.type', $this->types)
        ;
        $like          = sprintf('%%%s%%', $this->query);
        $originalQuery = $this->query;

        switch ($this->field) {
            default:
            case self::SEARCH_ALL:
                $searchQuery->where(
                    static function (Builder $q) use ($like): void {
                        $q->whereLike('accounts.id', $like);
                        $q->orWhereLike('accounts.name', $like);
                        $q->orWhereLike('accounts.iban', $like);
                    }
                );
                // meta data:
                $searchQuery->orWhere(
                    static function (Builder $q) use ($originalQuery): void {
                        $json = json_encode($originalQuery, JSON_THROW_ON_ERROR);
                        $q->where('account_meta.name', '=', 'account_number');
                        $q->whereLike('account_meta.data', $json);
                    }
                );

                break;

            case self::SEARCH_ID:
                $searchQuery->where('accounts.id', '=', (int) $originalQuery);

                break;

            case self::SEARCH_NAME:
                $searchQuery->whereLike('accounts.name', $like);

                break;

            case self::SEARCH_IBAN:
                $searchQuery->whereLike('accounts.iban', $like);

                break;

            case self::SEARCH_NUMBER:
                // meta data:
                $searchQuery->Where(
                    static function (Builder $q) use ($originalQuery): void {
                        $json = json_encode($originalQuery, JSON_THROW_ON_ERROR);
                        $q->where('account_meta.name', 'account_number');
                        $q->where('account_meta.data', $json);
                    }
                );

                break;
        }

        return $searchQuery->distinct()->get(['accounts.*']);
    }

    public function setField(string $field): void
    {
        $this->field = $field;
    }

    public function setQuery(string $query): void
    {
        $this->query = $query;
    }

    public function setTypes(array $types): void
    {
        $this->types = $types;
    }

    public function setUser(null|Authenticatable|User $user): void
    {
        if ($user instanceof User) {
            $this->user = $user;
        }
    }
}
