<?php
/**
 * TransactionJournalMeta.php
 * Copyright (C) 2016 Sander Dorigo
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace FireflyIII\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class TransactionJournalMeta
 *
 * @package FireflyIII\Models
 * @property-read \FireflyIII\Models\TransactionJournal $transactionjournal
 * @property integer $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property integer $transaction_journal_id
 * @property string $name
 * @property string $data
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\TransactionJournalMeta whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\TransactionJournalMeta whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\TransactionJournalMeta whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\TransactionJournalMeta whereTransactionJournalId($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\TransactionJournalMeta whereName($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\TransactionJournalMeta whereData($value)
 * @mixin \Eloquent
 */
class TransactionJournalMeta extends Model
{

    protected $dates    = ['created_at', 'updated_at'];
    protected $fillable = ['transaction_journal_id', 'name', 'data'];
    protected $table    = 'journal_meta';

    /**
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function transactionjournal(): BelongsTo
    {
        return $this->belongsTo('FireflyIII\Models\TransactionJournal');
    }
}
