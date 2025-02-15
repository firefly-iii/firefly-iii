<?php

/**
 * Recurrence.php
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

use FireflyIII\Casts\SeparateTimezoneCaster;
use FireflyIII\Support\Models\ReturnsIntegerIdTrait;
use FireflyIII\Support\Models\ReturnsIntegerUserIdTrait;
use FireflyIII\User;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Recurrence extends Model
{
    use ReturnsIntegerIdTrait;
    use ReturnsIntegerUserIdTrait;
    use SoftDeletes;

    protected $casts
                     = [
            'created_at'   => 'datetime',
            'updated_at'   => 'datetime',
            'deleted_at'   => 'datetime',
            'title'        => 'string',
            'id'           => 'int',
            'description'  => 'string',
            'first_date'   => SeparateTimezoneCaster::class,
            'repeat_until' => SeparateTimezoneCaster::class,
            'latest_date'  => SeparateTimezoneCaster::class,
            'repetitions'  => 'int',
            'active'       => 'bool',
            'apply_rules'  => 'bool',
            'user_id'                => 'integer',
            'user_group_id'                => 'integer',
        ];

    protected $fillable
                     = ['user_id', 'user_group_id', 'transaction_type_id', 'title', 'description', 'first_date', 'first_date_tz', 'repeat_until', 'repeat_until_tz', 'latest_date', 'latest_date_tz', 'repetitions', 'apply_rules', 'active'];

    protected $table = 'recurrences';

    /**
     * Route binder. Converts the key in the URL to the specified object (or throw 404).
     *
     * @throws NotFoundHttpException
     */
    public static function routeBinder(string $value): self
    {
        if (auth()->check()) {
            $recurrenceId = (int) $value;

            /** @var User $user */
            $user         = auth()->user();

            /** @var null|Recurrence $recurrence */
            $recurrence   = $user->recurrences()->find($recurrenceId);
            if (null !== $recurrence) {
                return $recurrence;
            }
        }

        throw new NotFoundHttpException();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    /**
     * Get all the notes.
     */
    public function notes(): MorphMany
    {
        return $this->morphMany(Note::class, 'noteable');
    }

    public function recurrenceMeta(): HasMany
    {
        return $this->hasMany(RecurrenceMeta::class);
    }

    public function recurrenceRepetitions(): HasMany
    {
        return $this->hasMany(RecurrenceRepetition::class);
    }

    public function recurrenceTransactions(): HasMany
    {
        return $this->hasMany(RecurrenceTransaction::class);
    }

    public function transactionCurrency(): BelongsTo
    {
        return $this->belongsTo(TransactionCurrency::class);
    }

    public function transactionType(): BelongsTo
    {
        return $this->belongsTo(TransactionType::class);
    }

    protected function transactionTypeId(): Attribute
    {
        return Attribute::make(
            get: static fn ($value) => (int) $value,
        );
    }
}
