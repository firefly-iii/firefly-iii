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

use Deprecated;
use FireflyIII\Enums\TransactionTypeEnum;
use FireflyIII\Support\Models\ReturnsIntegerIdTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class TransactionType extends Model
{
    use ReturnsIntegerIdTrait;
    use SoftDeletes;

    #[Deprecated] /** @deprecated */
    public const string DEPOSIT          = 'Deposit';

    #[Deprecated] /** @deprecated */
    public const string INVALID          = 'Invalid';

    #[Deprecated] /** @deprecated */
    public const string LIABILITY_CREDIT = 'Liability credit';

    #[Deprecated] /** @deprecated */
    public const string OPENING_BALANCE  = 'Opening balance';

    #[Deprecated] /** @deprecated */
    public const string RECONCILIATION   = 'Reconciliation';

    #[Deprecated] /** @deprecated */
    public const string TRANSFER         = 'Transfer';

    #[Deprecated] /** @deprecated */
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
        return TransactionTypeEnum::DEPOSIT->value === $this->type;
    }

    public function isOpeningBalance(): bool
    {
        return TransactionTypeEnum::OPENING_BALANCE->value === $this->type;
    }

    public function isTransfer(): bool
    {
        return TransactionTypeEnum::TRANSFER->value === $this->type;
    }

    public function isWithdrawal(): bool
    {
        return TransactionTypeEnum::WITHDRAWAL->value === $this->type;
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
