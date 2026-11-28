<?php

/**
 * TransactionJournalMeta.php
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
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

use function Safe\json_decode;
use function Safe\json_encode;

/**
 * @property TransactionJournal $transactionJournal
 */
final class TransactionJournalMeta extends Model
{
    use ReturnsIntegerIdTrait;
    use SoftDeletes;

    #[\Override]
    protected $fillable = ['transaction_journal_id', 'name', 'data', 'hash'];

    #[\Override]
    protected $table    = 'journal_meta';

    public function transactionJournal(): BelongsTo
    {
        return $this->belongsTo(TransactionJournal::class);
    }

    #[\Override]
    protected function casts(): array
    {
        return ['created_at' => 'datetime', 'updated_at' => 'datetime', 'deleted_at' => 'datetime'];
    }

    public function data(): Attribute
    {
        return Attribute::make(get: static fn ($value): mixed => json_decode((string) $value, false), set: static function ($value): array {
            $data = json_encode($value);

            return ['data' => $data, 'hash' => hash('sha256', $data)];
        });
    }

    public function transactionJournalId(): Attribute
    {
        return Attribute::make(get: static fn ($value): int => (int) $value);
    }
}
