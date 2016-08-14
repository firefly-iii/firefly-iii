<?php
/**
 * TransactionJournalMeta.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class TransactionJournalMeta
 *
 * @package FireflyIII\Models
 * @property-read \FireflyIII\Models\TransactionJournal $transactionjournal
 * @property integer                                    $id
 * @property \Carbon\Carbon                             $created_at
 * @property \Carbon\Carbon                             $updated_at
 * @property integer                                    $transaction_journal_id
 * @property string                                     $name
 * @property string                                     $data
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\TransactionJournalMeta whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\TransactionJournalMeta whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\TransactionJournalMeta whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\TransactionJournalMeta whereTransactionJournalId($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\TransactionJournalMeta whereName($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\TransactionJournalMeta whereData($value)
 * @mixin \Eloquent
 * @property string                                     $hash
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\TransactionJournalMeta whereHash($value)
 */
class TransactionJournalMeta extends Model
{

    protected $dates    = ['created_at', 'updated_at'];
    protected $fillable = ['transaction_journal_id', 'name', 'data', 'hash'];
    protected $table    = 'journal_meta';

    /**
     * @param $value
     *
     * @return mixed
     */
    public function getDataAttribute($value)
    {
        return json_decode($value);
    }

    /**
     * @param $value
     */
    public function setDataAttribute($value)
    {
        $data                     = json_encode($value);
        $this->attributes['data'] = $data;
        $this->attributes['hash'] = hash('sha256', $data);
    }

    /**
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function transactionJournal(): BelongsTo
    {
        return $this->belongsTo('FireflyIII\Models\TransactionJournal');
    }
}
