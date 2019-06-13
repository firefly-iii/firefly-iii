<?php
/**
 * TransactionJournalMeta.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
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

namespace FireflyIII\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class TransactionJournalMeta.
 *
 * @property string             $name
 * @property int                $transaction_journal_id
 * @property TransactionJournal $transactionJournal
 * @property string             $data
 * @property int                $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string $hash
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @method static bool|null forceDelete()
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\TransactionJournalMeta newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\TransactionJournalMeta newQuery()
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\TransactionJournalMeta onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\TransactionJournalMeta query()
 * @method static bool|null restore()
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\TransactionJournalMeta whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\TransactionJournalMeta whereData($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\TransactionJournalMeta whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\TransactionJournalMeta whereHash($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\TransactionJournalMeta whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\TransactionJournalMeta whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\TransactionJournalMeta whereTransactionJournalId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\TransactionJournalMeta whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\TransactionJournalMeta withTrashed()
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\TransactionJournalMeta withoutTrashed()
 * @mixin \Eloquent
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
