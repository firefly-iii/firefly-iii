<?php

/**
 * TransactionType.php
 * Copyright (c) 2019 james@firefly-iii.org
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

namespace FireflyIII\Models;

use FireflyIII\Support\Models\ReturnsIntegerIdTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @mixin IdeHelperTransactionType
 */
class TransactionType extends Model
{
    use ReturnsIntegerIdTrait;
    use SoftDeletes;

    #[\Deprecated]
    public const string DEPOSIT          = 'Deposit';

    #[\Deprecated]
    public const string INVALID          = 'Invalid';

    #[\Deprecated]
    public const string LIABILITY_CREDIT = 'Liability credit';

    #[\Deprecated]
    public const string OPENING_BALANCE  = 'Opening balance';

    #[\Deprecated]
    public const string RECONCILIATION   = 'Reconciliation';

    #[\Deprecated]
    public const string TRANSFER         = 'Transfer';

    #[\Deprecated]
    public const string WITHDRAWAL       = 'Withdrawal';

    protected $casts
                                         = [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    protected $fillable                  = ['type'];

    /**
     * Route binder. Converts the key in the URL to the specified object (or throw 404).
     *
     * @throws NotFoundHttpException
     */
    public static function routeBinder(string $type): self
    {
        if (!auth()->check()) {
            throw new NotFoundHttpException();
        }
        $transactionType = self::where('type', ucfirst($type))->first();
        if (null !== $transactionType) {
            return $transactionType;
        }

        throw new NotFoundHttpException();
    }

    public function isDeposit(): bool
    {
        return self::DEPOSIT === $this->type;
    }

    public function isOpeningBalance(): bool
    {
        return self::OPENING_BALANCE === $this->type;
    }

    public function isTransfer(): bool
    {
        return self::TRANSFER === $this->type;
    }

    public function isWithdrawal(): bool
    {
        return self::WITHDRAWAL === $this->type;
    }

    public function transactionJournals(): HasMany
    {
        return $this->hasMany(TransactionJournal::class);
    }

    protected function casts(): array
    {
        return [
            // 'type' => TransactionTypeEnum::class,
        ];
    }
}
