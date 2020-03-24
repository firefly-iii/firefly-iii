<?php
/**
 * PiggyBankEvent.php
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

use Carbon\Carbon;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class PiggyBankEvent.
 *
 * @property PiggyBank          $piggyBank
 * @property int                $transaction_journal_id
 * @property int                $piggy_bank_id
 * @property int                $id
 * @property Carbon             date
 * @property TransactionJournal transactionJournal
 * @property string             $amount
 * @property Carbon             created_at
 * @property Carbon             updated_at
 * @method static Builder|PiggyBankEvent newModelQuery()
 * @method static Builder|PiggyBankEvent newQuery()
 * @method static Builder|PiggyBankEvent query()
 * @method static Builder|PiggyBankEvent whereAmount($value)
 * @method static Builder|PiggyBankEvent whereCreatedAt($value)
 * @method static Builder|PiggyBankEvent whereDate($value)
 * @method static Builder|PiggyBankEvent whereId($value)
 * @method static Builder|PiggyBankEvent wherePiggyBankId($value)
 * @method static Builder|PiggyBankEvent whereTransactionJournalId($value)
 * @method static Builder|PiggyBankEvent whereUpdatedAt($value)
 * @mixin Eloquent
 */
class PiggyBankEvent extends Model
{
    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts
        = [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'date'       => 'date',
        ];
    /** @var array Fields that can be filled */
    protected $fillable = ['piggy_bank_id', 'transaction_journal_id', 'date', 'amount'];
    /** @var array Hidden from view */
    protected $hidden = ['amount_encrypted'];

    /**
     * @codeCoverageIgnore
     * @return BelongsTo
     */
    public function piggyBank(): BelongsTo
    {
        return $this->belongsTo(PiggyBank::class);
    }

    /**
     * @codeCoverageIgnore
     *
     * @param $value
     */
    public function setAmountAttribute($value): void
    {
        $this->attributes['amount'] = (string) $value;
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
