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

use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;

/**
 * FireflyIII\Models\TransactionJournalMeta
 *
 * @property int $id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property int $transaction_journal_id
 * @property string $name
 * @property mixed $data
 * @property string $hash
 * @property Carbon|null $deleted_at
 * @property-read \FireflyIII\Models\TransactionJournal $transactionJournal
 * @method static \Illuminate\Database\Eloquent\Builder|TransactionJournalMeta newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|TransactionJournalMeta newQuery()
 * @method static Builder|TransactionJournalMeta onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|TransactionJournalMeta query()
 * @method static \Illuminate\Database\Eloquent\Builder|TransactionJournalMeta whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TransactionJournalMeta whereData($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TransactionJournalMeta whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TransactionJournalMeta whereHash($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TransactionJournalMeta whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TransactionJournalMeta whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TransactionJournalMeta whereTransactionJournalId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TransactionJournalMeta whereUpdatedAt($value)
 * @method static Builder|TransactionJournalMeta withTrashed()
 * @method static Builder|TransactionJournalMeta withoutTrashed()
 * @mixin Eloquent
 */
class TransactionJournalMeta extends Model
{
    use SoftDeletes;
    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts
        = [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    /** @var array Fields that can be filled */
    protected $fillable = ['transaction_journal_id', 'name', 'data', 'hash'];
    /** @var string The table to store the data in */
    protected $table = 'journal_meta';

    /**
     * @codeCoverageIgnore
     *
     * @param $value
     *
     * @return mixed
     */
    public function getDataAttribute($value)
    {
        return json_decode($value, false);
    }

    /**
     * @codeCoverageIgnore
     *
     * @param $value
     */
    public function setDataAttribute($value): void
    {
        $data                     = json_encode($value);
        $this->attributes['data'] = $data;
        $this->attributes['hash'] = hash('sha256', $data);
    }

    /**
     * @codeCoverageIgnore
     * @return BelongsTo
     */
    public function transactionJournal(): BelongsTo
    {
        return $this->belongsTo(TransactionJournal::class);
    }
}
